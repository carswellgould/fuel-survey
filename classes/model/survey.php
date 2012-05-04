<?php
namespace Survey;

/**
 * Thrown when the next button is clicked
 *
 */
class SurveyUpdated extends \Fuel_Exception {};

class SurveySubQuestionsRevealed extends \Fuel_Exception {};

/**
 * Thrown when the back button is clicked
 */
class SurveyBack extends \Fuel_Exception {};

/**
 * Thrown when the last section has been completed
 */
class SurveyComplete extends \Fuel_Exception {};


class Model_Survey extends \Orm\Model {

	protected static $_properties = array('id', 'title', 'description');

	protected static $_has_many = array('sections');

	private $_active_section = null;

	private $_finished = false;

	private $_on_complete = "not set";

	private $_fieldset_settings = array();

	/**
	 *
	 *
	 *  @param array $data
	 *  @param bool $new
	 * 	@param View $view
	 *	@todo this currently calls even when the section is inactive. Shift this around so the logic doesnt get called unnecessarily.
	 */
	public static function forge($data = array(), $new = true, $view = null)
	{
		$active_section_id = null;
		if (isset($data['active_section']))
		{
			$active_section_id = $data['active_section'];
			unset($data['active_section']);
		}

		$survey = parent::forge($data, $new, $view);
		try
		{
			$survey->set_active_section($active_section_id ?: \Session::get('survey.'.$survey->id.'.active_section_id'));
		}
		catch(SurveyComplete $e)
		{
			//$survey->complete(\Session::get('survey.'.$survey->id.'.responses', array()));
		}

		return $survey;
	}

	public static function generate($id, $on_complete)
	{

		$survey = Model_Survey::find($id);

		if ($survey->is_complete())
		{
			$on_complete(array());
		}
		return $survey;

	}

	public function is_complete()
	{
		return $this->_finished;
	}

	public function complete($results)
	{

	}

	/**
	 *
	 *
	 * @param int $id
	 * @return Model_Survey
	 * @throws \UnexpectedValueException
	 * @throws SurveyComplete
	 */
	public function set_active_section($id)
	{
		try
		{
			if ($id === 0 or $id === null)
			{
				$query = Model_Section::find()
					->where('survey_id', $this->id);
			}
			else
			{
				$query = Model_Section::find()->where('id', $id);
			}

			$this->_active_section = $query->order_by('position', 'asc')
										//TODO probably needs a question position field but this will do for now
										->order_by('questions.id', 'asc')
										//->order_by('questions.answers.value', 'asc')
										->related('questions')
										->related('questions.answers')
										->get_one();

			$this->_active_section->generate_fieldset();

			if ($this->_active_section === null or $this->_active_section->survey_id !== $this->id)
			{
				throw new \UnexpectedValueException('We couldn\'t find the section with id ('.$id.')');
			}

		}
		catch (SurveyUpdated $e)
		{
			$this->_active_section = Model_Section::find()
				->where('survey_id', $this->id)
				->where('position', '>', $this->_active_section->position)
				->order_by('position', 'asc')
				->get_one();
			if ($this->_active_section === null)
			{
				$this->_finished = true;
				throw new SurveyComplete;
			}
			$this->_active_section->generate_fieldset();
		}
		catch (SurveySubQuestionsRevealed $e)
		{
			//don't do anything in particular
		}
		catch(SurveyBack $e)
		{

			$this->_active_section = Model_Section::find()
				->where('survey_id', $this->id)
				->where('position', '<', $this->_active_section->position)
				->order_by('position', 'desc')
				->get_one();
				$this->_active_section->generate_fieldset();
		}

		\Session::set('survey.'.$this->id.'.active_section_id', $this->_active_section->id);
		return $this;
	}

	public function get_responses()
	{
		return \Session::get('survey.'.$this->id.'.responses', array());
	}


	/**
	 * Alias for render, allows using the survey as a string (magic method)
	 *
	 * @return string
	 */
	public function __toString()
	{
		try
		{
			return $this->render();
		}
		catch (\Exception $e)
		{
			if (\Fuel::$env == \Fuel::PRODUCTION)
			{
				\Log::error('There was a problem rendering the survey');
				return '';
			}
			else
			{
				\Error::show_php_error($e);
			}
		}
	}

	public function get_progress () {
		$all = \DB::select('id', 'position')
					->from('sections')
					->where('survey_id', $this->id)
					->order_by('position', 'asc')
					->as_assoc()
					->execute()
					->as_array();

		$before = 0;
		foreach ($all as $section) {
			if ($section['id'] == $this->_active_section->id)
			{
				break;
			}
			++$before;
		}

		return round((100 / sizeof($all)) * $before);
	}


	/**
	 * Renders the survey
	 *
	 * @return string
	 */
	public function render()
	{
		if ( ! $this->_finished)
		{
			$view = \View::forge('survey/survey');
			$view->section_title = $this->_active_section->title;
			$view->section_id = $this->_active_section->id;
			$view->set('section', $this->_active_section->render(), false);
		}
		else
		{
			$view = \View::forge('survey/complete');
			$view->results = \Session::get('survey.'.$this->id.'.responses', array());
		}

		$view->survey = $this;


		return $view->render();
	}
}