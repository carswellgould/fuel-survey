<?php
namespace Survey;

class Model_Section extends \Orm\Model {

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
	 * Generates the field set for the section
	 * (I think more info on that would be appropriate)
	 *
	 * @return Model_Section
	 */
	public function generate_fieldset()
	{
		$fieldset = \Fieldset::forge('survey-'.$this->id, $this->_fieldset_data);

		if (\Arr::get($this->_fieldset_data, 'survey_template', true))
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


		foreach ($this->questions as $question)
		{
			//->add( 'name', 'Label', array( 'type' => 'select', 'options' => $options, 'value' => 'selected_values_array_key' ), array( array('required'), )

			switch($question->type)
			{
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
						'value' => 'h1',
						'class' => ''
					));
					break;
			}
		}

		//populate from session
		$session = \Session::get('survey.'.$this->survey_id.'.responses', array());
		$session_values = array();

		//normalise
		if (isset($session[$this->id]))
		{
			foreach ($session[$this->id] as $question_id => $value)
			{
				$session_values['question-'.$question_id] = $value;
			}
		}

		//the second param means that post data overrides what we set (in case the user updates their selection)
		$fieldset->populate($session_values, true);

		$sections = $this->survey->get_sections();

		//back button
		if (current($sections)->id != $this->id)
		{
			$fieldset->add('back-'.$this->id, null, array(
				'type' => "submit",
				'value' => 'Back',
			));
		}


		//next button
		$submit = (end($sections)->id == $this->id) ? 'Finish' : 'Next';

		$fieldset->add('submit-'.$this->id, null, array(
			'type' => "submit",
			'value' => $submit,
		));

		$this->_fieldset = $fieldset;

		//validate - note, this runs when a different form is posted too :(
		if ($fieldset->validation()->run())
		{
			if ($fieldset->validation()->validated('back-'.$this->id) and $fieldset->validation()->validated('back-'.$this->id) !== NULL)
			{
				throw new SurveyBack();
			}
			if ($fieldset->validation()->validated('submit-'.$this->id))
			{
				$responses = array();
				$qid = null;
				foreach ($fieldset->field() as $key => $field)
				{
					//we dont need to store the submit button - save a bit of session space
					if ($key == 'submit-'.$this->id or $key == 'back-'.$this->id)
					{
						continue;
					}

					$val = $fieldset->validation()->input($key);
					///strips out the question- and adds the value to be stored
					$qid = preg_replace("/[^0-9]/", "", $key);
					$session[$this->id][$qid] = (string)$fieldset->validation()->input($key);
				}
				\Session::set('survey.'. $this->survey_id.'.responses', $session);
				throw new SurveyUpdated();
			}
		}
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function render ()
	{
		return $this->_fieldset->build();
	}
}
