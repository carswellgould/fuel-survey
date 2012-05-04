<?php
namespace Survey;

class Model_Section extends \Orm\Model
{

	protected static $_properties = array('id', 'title', 'description', 'position', 'survey_id');

	protected static $_has_many = array('questions');

	protected static $_belongs_to = array('survey');


	/**
	 * @var array
	 *
	 * Holds IDs of questions that were shown before the current request was sent,
	 * Which basically means, the questions renderde by the previous
	 * generate_fieldset() call.
	 *
	 * IDs are retrieved from session (survey.<survey_id>.questions_shown)
	 */
	private $_questions_shown = array();

	/**
	 * @var array
	 *
	 * Holds IDs of questions that are added to the fieldset, to be stored in
	 * the session. (survey.<survey_id>.questions_shown).
	 *
	 * The IMPORTANT part to note is that the session key is only populate once
	 * generate_fieldset is -done- adding questions.
	 *
	 * This array is added to as questions are added
	 * as a result of generate_fieldset- more directly by _add_question.
	 */
	private $_questions_added = array();

	/**
	 * @var bool
	 *
	 * Whether or not any new subquestions were revealed for any questions in the
	 * section. If this is set to true, SurveySubQuestionsRevealed will be thrown.
	 */
	private $_subquestions_revealed = false;



	private $_fieldset = null;

	public $_fieldset_options = array();


	/**
	 *
	 * @param array $data
	 * @param bool $new
	 * @param \View $view
	 * @return Model_Section
	 */
	public static function forge($data = array(), $new = true, $view = null)
	{
		$_fieldset_options = \Arr::get($data, 'fieldset', array());
		unset($data['fieldset']);

		$section = parent::forge($data, $new, $view);
		$section->_fieldset_options = $_fieldset_options;
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
	 * @throws SurveyUpdated
	 */
	public function generate_fieldset()
	{
		//(0) start a fieldset
		$fieldset = \Fieldset::forge('survey-'.$this->id, $this->_fieldset_options);


		// (1) Find out which questions were shown before
		$this->_questions_shown = \Session::get(
			'survey.'.$this->survey_id.'.questions_shown',
			array()
		);

		\Log::info('gen_fieldset:' . str_replace("\n", " ", var_export($this->_questions_shown, true)));


		// (2) Add all the questions to the fieldset
		// since subquestions will automatically be added by _add_question, we only
		// want 'main' questions.

		$questions = array_filter($this->questions, function($question) {
			//only keep those with a null parent_id
			return is_null($question->parent_id);
		});

		foreach ($questions as $question)
		{
			$this->_add_question($question, $fieldset);
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


		// (4) Add navigation (submit) buttons ([Back,] Next|Finish)
		// submit_container is mixed into the fieldset template in (5)
		$submit_container = '<div class="submit">{back}{next}</div>';

		// Back button
		//only generate a back button if we're not on the first section
		$back = (current($sections)->id != $this->id)
			? \Form::submit('back-'.$this->id, 'Back')
			: '';

		// Next|Finish Button
		$next = \Form::submit(
			'submit-'.$this->id,
			//determine whether it should say finish or next
			(end($sections)->id == $this->id) ? 'Finish' : 'Next'
		);

		$submit_container = str_replace(
			array('{back}', '{next}'),
			array($back, $next),
			$submit_container
		);



		// (5) Set fieldset html template (use the one below, the user's custom or the fuel default)
		if (\Arr::get($this->_fieldset_options, 'use_survey_template', true))
		{
			$fieldset->form()->set_config('form_template', '{open}{fields}'.$submit_container.'{close}');

			$fieldset->form()->set_config(
				'multi_field_template',
				"<div class=\"question\"><div class=\"{error_class} question-title\">{group_label}{required}</div><div class=\"{error_class} answer\">{fields}<div class=\"survey-input\">{label} {field} </div>{fields}{error_msg}</div></div>\n"
			);

			$fieldset->form()->set_config(
				'field_template',
				"\t\t<div class=\"question\">\n\t\t\t<div class=\"{error_class} question-title\">{label}{required}</div>\n\t\t\t<div class=\"{error_class} answer\"><div class=\"survey-input\">{field}</div> {error_msg}</div>\n\t\t</div>\n"
			);
		}

		if (\Arr::get($this->_fieldset_options, 'form_template', false))
		{
			$fieldset->form()->set_config(
				'form_template',
				\Arr::get($this->_fieldset_options, 'form_template', false)
			);
		}

		if (\Arr::get($this->_fieldset_options, 'multi_field_template', false))
		{
			$fieldset->form()->set_config(
				'multi_field_template',
				\Arr::get($this->_fieldset_options, 'multi_field_template', false)
			);
		}

		if (\Arr::get($this->_fieldset_options, 'field_template', false))
		{
			$fieldset->form()->set_config(
				'field_template',
				\Arr::get($this->_fieldset_options, 'field_template', false)
			);
		}


		$this->_fieldset = $fieldset;


		// (6) Save the questions we added in a session
		\Session::set(
			'survey.'.$this->survey_id.'.questions_shown',
			$this->_questions_added
		);


		// (7) Validate the form

		if(\Input::post('back-'.$this->id))
		{ //Back button clicked
			throw new SurveyBack;
		}

		if (\Input::post('submit-'.$this->id) and $fieldset->validation()->run())
		{
			// next/finish button was clicked
			// collect responses from form
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
			if($this->_subquestions_revealed)
			{
				throw new SurveySubQuestionsRevealed();
			}
			throw new SurveyUpdated();
		}


		// (7) Done. Support daisy-chaining
		return $this;
	}


	/**
	 * Adds a question to the given fieldset
	 *
	 * @param Model_Question $question
	 * @param \Fieldset $fieldset
	 * @return Model_Section (daisy-chaining)
	 */
	private function _add_question(Model_Question $question, \Fieldset $fieldset)
	{
		//fielset::add parameter structure:
		//->add( 'name', 'Label', array( 'type' => 'select', 'options' => $options, 'value' => 'selected_values_array_key' ), array( array('required'), )

		switch($question->type)
		{
			// TODO: add more input types(?)
			case 'SELECT':
			case 'RADIO':
				$options = array();
				$answers = self::_sort_answers_by_id($question->answers);
				foreach ($answers as $answer)
				{
					$options[$answer->value] = $answer->answer;
				}
				$fieldset->add('question-'.$question->id, $question->question, array(
					'type' => strtolower($question->type),
					'options' => $options,
				));
				break;
		}

		// Add any subquestions this question might have
		// _add_subquestions does the logic to determine whether or not add them.
		$this->_add_subquestions($question, $fieldset);

		//remember the IDs of the fields added
		$this->_questions_added[] = $question->id;

		return $this;
	}


	/**
	 * Checks whether a given question has any subquestions that need displaying
	 *
	 * This first checks whether the question has been answered, then checks
	 * whether there are any subquestions, particularly for the chosen answer.
	 *
	 * If such subquestions are found, add them to the fieldset, and fill in their
	 * answers, if available.
	 *
	 * Also clear any answers to subquestions that no longer apply
	 *
	 * @param Model_Question $question
	 * @param \Fieldset $fieldset
	 * @return Model_Survey (daisy-chaining)
	 */
	private function _add_subquestions(Model_Question $question, \Fieldset $fieldset)
	{
		$parent_value = $this->_get_question_answer($question, $fieldset);

		if ( ! is_null($parent_value))
		{
			if (count($question->subquestions) > 0)
			{
				foreach ($question->subquestions as $subquestion)
				{
					if ($subquestion->section_id != $this->id)
					{
						continue;
					}
					if($parent_value == $subquestion->parent_value)
					{
						$this->_add_question($subquestion, $fieldset);
						if ( ! in_array($subquestion->id, $this->_questions_shown))
						{ //this would mean that we're showing a subquestion that was not shown before
							$this->_subquestions_revealed = true;
						}
					}
					else
					{
						//Clear answers for irrelevant subquestion, if any
						$session = \Session::get('survey.'.$this->survey_id.'.responses', array());
						if (isset($session[$this->id]) and isset($session[$this->id][$subquestion->id]))
						{
							unset($session[$this->id][$subquestion->id]);
							\Session::delete('survey.'.$this->survey_id.'.responses.'.$this->id.'.'.$subquestion->id);
						}
					}
				}
			}
		}

		return $this;
	}


	/**
	 * Given a question and a fieldset, figures out whether a question has been
	 * answered and returns that answer.
	 *
	 * This will first check the session for the question's answer value, then
	 * check POST and possibly override it.
	 *
	 * @param Model_Question $question
	 * @param \Fieldset @fieldset
	 * @return string|null
	 *
	 */
	private function _get_question_answer(Model_Question $question, \Fieldset $fieldset)
	{
		$question_value = null;
		$session = \Session::get('survey.'.$this->survey_id.'.responses', array());

		if (isset($session[$this->id]) and isset($session[$this->id][$question->id]))
		{
			$question_value = $session[$this->id][$question->id];
		}


		if ($fieldset->validation()->run())
		{
			if (trim($fieldset->validation()->validated('question-' . $question->id)) != '')
			{
				$question_value = (string)$fieldset->validation()->validated('question-' . $question->id);
			}
		}
		return $question_value;
	}

	public function set_fieldset_options(array $options)
	{
		$this->_fieldset_options += $options;
	}


	/**
	 * Render the fieldset
	 *
	 * Uses Fuels' \Fieldset Build method
	 *
	 * @return string
	 */
	public function render ()
	{
		return $this->_fieldset->build();
	}


	private static function _sort_answers_by_id($answers)
	{ //yay hacky.
		$sorted_answers = $answers;
		$sortanswers = function ($a, $b)
		{
			if ($a->id == $b->id)
			{
				return 0;
			}
			return ($a->id < $b->id) ? -1 : 1;
		};
		usort($sorted_answers, $sortanswers);
		return $sorted_answers;
	}
}
