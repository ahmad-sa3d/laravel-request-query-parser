<?php

/**
 * Abstract Prepare class
 * this abstract class should be extened by every Model preparer class
 *
 * @package saad\laravel-query-loader
 * @author Ahmed Saad <a7mad.sa3d.2014>
 * @version 1.0.0
 * @license [<url>] MIT
 *
 * @property string $table table name
 * @property string $model model class name
 * @property array $in_context_info array of context information
 * @property FractalRequestParser $parser Fractal Request Parser instance
 */

namespace Saad\QueryParser;

use Saad\QueryParser\Contracts\ModelPreparerContract;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Saad\Fractal\FractalRequestParser;

abstract class PreparerAbstract implements ModelPreparerContract {

	/**
	 * Preparer Table name
	 * @var string
	 */
	protected $table;

	/**
	 * Model
	 * @var string
	 */
	protected $model;

	/**
	 * Context info Array
	 * @var array
	 */
	protected $in_context_info = [];

	/**
	 * Fracrtal Request parser instance
	 * @var FractalRequestParser
	 */
	protected $parser;

	/**
	 * Constructor
	 * @throws RuntimeException thrown if model doesnot exists
	 */
	public function __construct()
	{
		$this->parser = FractalRequestParser::getInstance();

		// Set table
		if (!$this->table ) {
			$this->table = strtolower(str_plural(str_replace('Preparer', '', class_basename(get_called_class()))));
		}

		// Set Model
		if (!$this->model ) {
			$this->model = '\App\\' . ucfirst(str_singular($this->table));
		}

		if (!class_exists($this->model)) {
			throw new \RuntimeException("{$this->model} Model Not Found");
		}
	}

	/**
	 * Get Info Array for a specific context
	 * 
	 * @param  string $context_model_name Context name (basename)
	 * @return array                     context info
	 */
	final public function getInfo($context_model_name)
	{
		if (is_null($context_model_name)) {
			return [];
		}

		if (!array_key_exists($context_model_name, $this->in_context_info)) {
			throw new \InvalidArgumentException("no context info exists for {$context_model_name}");
		}

		return $this->in_context_info[$context_model_name];
	}
	
	/**
	 * Prepare Governorate Query
	 * 
	 * @param  QueryBuilder $query         	Governorate Query
	 * @param  string $context_model 		Context Model Name that we are prepaing its governorate
	 * @param  string $namespace     		namespace for nesting calls needed to prefixed for FractalRequestParser check
	 * @return QueryBuilder                	Prepared Governorate Query
	 */
	final public function prepare($query = null, $context_model = null, $namespace = null)
	{
		if (is_null($query)) {
			$query = $this->model::query();
		}

        $namespace = $this->getParserNamespace($namespace, $context_model);

        $this->selectBasicFields($query);

        // Call Extra Prepare
        if (method_exists($this, 'extendPrepare')) {
        	$this->extendPrepare($query, $namespace);
        }

		// return Resulted Query
		return $query;
	}

	/**
	 * Get Parser Latest Namespace
	 * 
	 * @param  string $namespace current context namespace
	 * @return string            Latest namespace
	 */
	protected function getParserNamespace($namespace, $context_model)
	{
		$context_info = $this->getInfo($context_model);

		if ($context_info) {
			return $namespace . $context_info['context_relation_name'] . '.';
		} else {
			return $namespace;
		}
	}

	/**
	 * Select Basic Fields On Query
	 * 
	 * @param  Builder $query Context Query
	 */
	protected function selectBasicFields($query)
	{
		$query->select("{$this->table}.id");

        if(is_a($query, HasMany::class)) {
        	$query->addSelect($query->getQualifiedForeignKeyName());
        }
	}
}