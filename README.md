# Fuel Survey

A wrapper for fieldsets that lets you quickly create surveys, questionnaires and wizards on your site.

# Install

1. In the repository's root directory, run `git clone git://github.com/carswellgould/fuel-survey.git fuel/packages/survey`.
2. Then install the database tables by running `php oil refine migrate --packages=survey`

# Usage

1. Add your survey to the database. Each survey has at least one section, each section has at least one question, each question has at least one answer.
2. In your controller, instanciate and render the questionnaire as follows

```php
$data = array();
//this gets the survey with ID 1 from the database
$data['questionnaire'] = Model_Survey::find(1);
$view = View::forge('welcome/index', $data);
```

Fork and pull request any useful changes you make.

# Features

* Database-driven 
* Multiple sections to each survey
* Sub-questions


# Limitations

* Currently only works as multiple choice - if you'd like more do make a pull request
* Back button in browser doesn't work as a user might expect

# Todo

* More customisation of text/templates
* Add default CSS styles
* Add (optional) JS to help with UX
* Figure out a way to allow the browser back button to work
