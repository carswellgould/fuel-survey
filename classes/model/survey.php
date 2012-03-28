<?php
namespace Survey;

class SurveyUpdated extends \Fuel_Exception {};
class SurveyBack extends \Fuel_Exception {};
class SurveyComplete extends \Fuel_Exception {};

class Model_Survey extends \Orm\Model {

	protected static $_properties = array('id', 'title', 'description');

	protected static $_has_many = array('sections');

	private $_active_section = null;

	private $_finished = false;


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

			if (isset($data['complete']) and is_function($data['complete']))
			{
				$data['complete'](\Session::get('survey.'.$survey->id.'.responses', array()));
			}
		}

		//	$survey->_process_active_section();
		return $survey;
	}


	//dont call this anywhere (other than in the forge above). things will go wrong.
	/*public function _process_active_section()
	{
		$this->current_section = $sur
	}*/


	/**
	 *
	 *
	 * @param int $id
	 * @return Model_Survey
	 * @throws \UnexpectedValueException
	 * @throws SurveyComplete	 *
	 */
	public function set_active_section($id)
	{
		try
		{
			if ($id === 0 or $id === null)
			{
				$this->_active_section = Model_Section::find()
					->where('survey_id', $this->id)
					->order_by('position', 'asc')
					->get_one();

				$this->_active_section->generate_fieldset();
			}
			else
			{
				$this->_active_section = Model_Section::find($id);
				$this->_active_section->generate_fieldset();

				if ($this->_active_section === null or $this->_active_section->survey_id !== $this->id)
				{
					throw new \UnexpectedValueException('We couldn\'t find the section with id ('.$id.')');
				}
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
		catch(SurveyBack $e)
		{

			$this->_active_section = Model_Section::find()
				->where('survey_id', $this->id)
				->where('position', '<', $this->_active_section->position)
				->order_by('position', 'desc')
				->get_one()
				->generate_fieldset();
		}

		\Session::set('survey.'.$this->id.'.active_section_id', $this->_active_section->id);
		return $this;
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


	/**
	 * Renders the survey
	 *
	 * @return string
	 */
	public function render()
	{
		if ( ! $this->_finished)
		{
			$view = \View::forge('survey');
		}
		else
		{
			$view = \View::forge('complete');
			$view->results = \Session::get('survey.'.$this->id.'.responses', array());
		}

		$current_section = current($this->sections);
		$view->survey = $this;
		$view->set('section', $this->_active_section->render(), false);

		return $view->render();
	}
}