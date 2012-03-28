<?php
namespace Survey;

class Model_Section extends \Orm\Model
{

	protected static $_properties = array('id', 'title', 'description', 'position', 'survey_id');

	protected static $_has_many = array('questions');

	protected static $_belongs_to = array('survey');

	private $_fieldset = null;

	public $_fieldset_data = array();


	/**
	 *
	 * @param array $data
	 * @param bool $new
	 * @param \View $view
	 * @return Model_Section
	 */
	public static function forge($data = array(), $new = true, $view = null)
	{
		$fieldset_data = \Arr::get($data, 'fieldset', array());
		unset($data['fieldset']);

		$section = parent::forge($data, $new, $view);
		$section->_fieldset_data = $fieldset_data;
		return $section;
	}


	/**
	 * Generates the fieldset for this survey section
	 *
	 * - Selects the correct form template to use
	 * - Adds all the question from this section (see has_many relation) as fields
	 * in the fieldset
	 * - Populates these fields with possibly available answers
	 * - Renders Back, Next|Finish buttons
	 * - Validates any form input and stores responses in session
	 *
	 * See numbered steps in the method
	 *
	 * @return Model_Section (daisy chaining)
	 */
	public function generate_fieldset()
	{
		$fieldset = \Fieldset::forge('survey-'.$this->id, $this->_fieldset_data);

		// (1) Set fieldset html template (use the one below, or the fuel default)
		if (\Arr::get($this->_fieldset_data, 'use_survey_template', true))
		{
			$fieldset->form()->set_config('form_template', '{open}{fields}{close}');

			$fieldset->form()->set_config(
				'multi_field_template',
				"<div class=\"question\"><div class=\"{error_class} question-title\">{group_label}{required}</div><div class=\"{error_class} answer\">{fields}<div class=\"survey-input\">{field} {label}</div>{fields}{error_msg}</div></div>\n"
			);

			$fieldset->form()->set_config(
				'field_template',
				"\t\t<div class=\"question\">\n\t\t\t<div class=\"{error_class} question-title\">{label}{required}</div>\n\t\t\t<div class=\"{error_class} answer\"><div class=\"survey-input\">{field}</div> {error_msg}</div>\n\t\t</div>\n"
			);
		}

		// (2) Add all the questions to the fieldset
		foreach ($this->questions as $question)
		{
			//fielset::add parameter structure:
			//->add( 'name', 'Label', array( 'type' => 'select', 'options' => $options, 'value' => 'selected_values_array_key' ), array( array('required'), )

			switch($question->type)
			{
				// TODO: add more input types(?)
				case 'SELECT':
				case 'RADIO':
					$options = array();
					foreach ($question->answers as $answer)
					{
						$options[$answer->value] = $answer->answer;
					}
					$fieldset->add('question-'.$question->id, $question->question, array(
						'type' => strtolower($question->type),
						'options' => $options,
					));
					break;
			}
		}

		// (3) populate fieldset with available responses from session
		$session = \Session::get('survey.'.$this->survey_id.'.responses', array());
		$session_question_responses = array();

		// Normalise question-value pairs for use in fieldset::populate method
		if (isset($session[$this->id]))
		{
			foreach ($session[$this->id] as $question_id => $value)
			{
				$session_question_responses['question-'.$question_id] = $value;
			}
		}

		// The second param means that POST data overrides what we set
		// (in case the user updates their selection)
		$fieldset->populate($session_question_responses, true);

		$sections = $this->survey->get_sections(); //orm overloading makes this happen

		// (4) Add navigation buttons ([Back,] Next|Finish)
		// Back button
		if (current($sections)->id != $this->id) //we're not at the first section
		{
			$fieldset->add('back-'.$this->id, null, array(
				'type' => "submit",
				'value' => 'Back',
			));
		}

		// Next|Finish Button
		$submit = (end($sections)->id == $this->id) ? 'Finish' : 'Next';

		$fieldset->add('submit-'.$this->id, null, array(
			'type' => "submit",
			'value' => $submit,
		));

		$this->_fieldset = $fieldset;


		// (5) Validate the form
		if ($fieldset->validation()->run())
		{
			// back button was clicked
			if ($fieldset->validation()->validated('back-'.$this->id) and $fieldset->validation()->validated('back-'.$this->id) !== NULL)
			{
				throw new SurveyBack();
			}
			// next/finish button was clicked
			if ($fieldset->validation()->validated('submit-'.$this->id))
			{
				//collect responses from form
				$responses = array();
				$qid = null;
				foreach ($fieldset->field() as $key => $field)
				{
					//we dont need to store the submit button - save a bit of session space
					if ($key != 'submit-'.$this->id and $key != 'back-'.$this->id)
					{
						$val = $fieldset->validation()->input($key);

						//strips out the question- and adds the value to be stored
						$qid = preg_replace("/[^0-9]/", '', $key);
						$session[$this->id][$qid] = (string)$fieldset->validation()->validated($key);
					}
				}
				\Session::set('survey.'. $this->survey_id.'.responses', $session);
				throw new SurveyUpdated();
			}
		}

		// (6) Done. Support daisy-chaining
		return $this;
	}


	/**
	 * Render the fieldset
	 *
	 * Uses Fuels' Fieldset Build method
	 *
	 * @return string
	 */
	public function render ()
	{
		return $this->_fieldset->build();
	}
}
