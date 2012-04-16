<?php
/**
 * Fuel Survey
 *
 * A Fuel package to help create surveys and questionnaires
 *
 * @package    Survey
 * @version    0.1
 * @author     Carswell Gould
 * @copyright  2012 Carswell Gould
 * @link       http://github.com/carswellgould
 */

Autoloader::add_core_namespace('Survey');

Autoloader::add_classes(array(
	'Survey\\Model_Survey'		=> __DIR__.'/classes/model/survey.php',
	'Survey\\Model_Section'		=> __DIR__.'/classes/model/section.php',
	'Survey\\Model_Question'	=> __DIR__.'/classes/model/question.php',
	'Survey\\Model_Answer'		=> __DIR__.'/classes/model/answer.php',
	'Survey\\Model_Response'	=> __DIR__.'/classes/model/response.php',
));


/* End of file bootstrap.php */
