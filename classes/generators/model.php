<?php

/**
 * Generate a new eloquent model, including
 * relationships.
 *
 * @package 	bob
 * @author 		Dayle Rees
 * @copyright 	Dayle Rees 2012
 * @license 	MIT License <http://www.opensource.org/licenses/mit>
 */
class Generators_Model extends Generator
{
	/**
	 * Enable the timestamps string in models?
	 *
	 * @var string
	 */
	private $_timestamps = '';

	/**
	 * Start the generation process.
	 *
	 * @return void
	 */
	public function __construct($args)
	{
		parent::__construct($args);

		// we need a model name
		if ($this->class == null)
			Common::error('You must specify a model name.');

		// load any command line switches
		$this->_settings();

		// start the generation
		$this->_model_generation();

		// write filesystem changes
		$this->writer->write();
	}

	/**
	 * This method is responsible for generation all
	 * source from the templates, and populating the
	 * files array.
	 *
	 * @return void
	 */
	private function _model_generation()
	{
		$prefix = ($this->bundle == DEFAULT_BUNDLE) ? '' : Str::classify($this->bundle).'_';

		// set up the markers for replacement within source
		$markers = array(
			'#CLASS#'		=> $prefix.$this->class_prefix.$this->class,
			'#LOWER#'		=> $this->lower,
			'#TIMESTAMPS#'	=> $this->_timestamps
		);

		// loud our model template
		$template = Common::load_template('model/model.tpl');

		// holder for relationships source
		$relationships_source = '';

		// holder for rule source
		$rule_source = '';

		// loop through our relationships
		foreach ($this->arguments as $relation)
		{
			// if we have a valid relation
			if(! strstr($relation, ':')) continue;

			// split
			$relation_parts = explode(':', Str::lower($relation));

			// we need two parts
			if(! count($relation_parts) == 2) continue;

			// markers for relationships
			$rel_markers = array(
				'#SINGULAR#'		=> Str::lower(Str::singular($relation_parts[1])),
				'#PLURAL#'			=> Str::lower(Str::plural($relation_parts[1])),
				'#WORD#'			=> Str::classify(Str::singular($relation_parts[1])),
				'#WORDS#'			=> Str::classify(Str::plural($relation_parts[1]))
			);

			// markers for rules
			$rule_markers = array(
				'#FIELD#'		=> Str::lower($relation_parts[0]),
				'#OPTIONS#'		=> Str::lower(implode(':', array_slice($relation_parts, 1))),
			);

			// start with blank
			$relationship_template = '';

			// start with blank
			$rule_template = '';

			// use switch to decide which template
			switch ($relation_parts[0])
			{
				case "has_many":
				case "hm":
					$relationship_template = Common::load_template('model/has_many.tpl');
					break;
				case "belongs_to":
				case "bt":
					$relationship_template = Common::load_template('model/belongs_to.tpl');
					break;
				case "has_one":
				case "ho":
					$relationship_template = Common::load_template('model/has_one.tpl');
					break;
				case "has_and_belongs_to_many":
				case "hbm":
					$relationship_template = Common::load_template('model/has_and_belongs_to_many.tpl');
					break;
				default:
					$rule_template = Common::load_template('model/rule.tpl');
					break;

			}

			// add it to the source
			$relationships_source .= Common::replace_markers($rel_markers, $relationship_template);

			// add it to the source
			$rule_source .= Common::replace_markers($rule_markers, $rule_template);
		}

		// add a marker to replace the relationships stub
		// in the model template
		$markers['#RELATIONS#'] = $relationships_source;

		// if we have rules
		if (! empty($rule_source))
		{
			// add rules template
			$rules_template = Common::load_template('model/rules.tpl');

			// add a marker to replace the relationships stub
			// in the model template
			$rules_markers = array(
				'#RULE#' => $rule_source,
			);

			// add rule to rules
			$rules_source = Common::replace_markers($rules_markers, $rules_template);

			// add a marker to replace the relationships stub
			// in the model template
			$markers['#RULES#'] = $rules_source;
		}

		// add the generated model to the writer
		$this->writer->create_file(
			'Model',
			$prefix.$this->class_prefix.$this->class,
			$this->bundle_path.'models/'.$this->class_path.$this->lower.EXT,
			Common::replace_markers($markers, $template)
		);
	}

	/**
	 * Alter generation settings from artisan
	 * switches.
	 *
	 * @return void
	 */
	private function _settings()
	{
		if(Common::config('timestamps') or Common::config('t'))
			$this->_timestamps = "\tpublic static \$timestamps = true;\n\n";
	}
}
