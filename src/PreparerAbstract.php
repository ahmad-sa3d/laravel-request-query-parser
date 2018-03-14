<?php

/**
 * Abstract Prepare class
 * this abstract class should be extened by every Model preparer class
 *
 * @package saad/request-query-parser
 * @author Ahmed Saad <a7mad.sa3d.2014>
 * @version 1.0.0
 * @license MIT MIT
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

		// Set Model
		if (!$this->model ) {
			$this->model = '\App\\' . ucfirst(str_replace('Preparer', '', class_basename(get_called_class())));
		}

		if (!class_exists($this->model)) {
			throw new \RuntimeException("{$this->model} Model Not Found");
		}

		// Set table
		if (!$this->table ) {
			$this->table = strtolower(str_plural(snake_case(class_basename($this->model))));
		}
	}

	/**
	 * Get Info Array for a specific context
	 * 
	 * @param  string $context_model_name Context name (basename)
	 * @return array                     context info
	 */
	final public function getInfo($context_model_name, $context_info_key = null)
	{
		if (is_null($context_model_name)) {
			return [];
		}

		if (!array_key_exists($context_model_name, $this->in_context_info)) {
			throw new \InvalidArgumentException("no context info exists for {$context_model_name}");
		}

		if ($context_info_key && !array_key_exists($context_info_key, $this->in_context_info[$context_model_name])) {
			throw new \InvalidArgumentException("no context info exists for {$context_model_name} with the key of {$context_info_key}");
		}

		return $context_info_key ? $this->in_context_info[$context_model_name][$context_info_key] : $this->in_context_info[$context_model_name];
	}
	
	/**
	 * Prepare Governorate Query
	 * 
	 * @param  QueryBuilder $query         	Governorate Query
	 * @param  string $context_model 		Context Model Name that we are prepaing its governorate
	 * @param  string $namespace     		namespace for nesting calls needed to prefixed for FractalRequestParser check
	 * @return QueryBuilder                	Prepared Governorate Query
	 */
	final public function prepare($query = null, $context_model = null, $namespace = null, $context_info_key = null)
	{
		// Refresh Parser if neede, this is important while Unit Testing
		$this->parser->refreshIfNeeded();

		if (is_null($query)) {
			$query = $this->model::query();
		}

        $namespace = $this->getParserNamespace($namespace, $context_model, $context_info_key);

        $this->selectBasicFields($query);

        // Call Extra Prepare
        if (method_exists($this, 'extendPrepare')) {
        	$this->extendPrepare($query, $namespace);
        }

        // dump($this->parser->getOptions(trim($namespace, '.')));
        $clean_namespace = trim($namespace, '.');
        // Check Order
        $this->checkOrder($query, $clean_namespace);

		// Check wheres
        $this->checkWheres($query, $clean_namespace);

        // Check Limit
        $this->checkLimit($query, $clean_namespace);

        // Check Offset
        $this->checkOffset($query, $clean_namespace);
		
		// return Resulted Query
		return $query;
	}

	/**
	 * Get Parser Latest Namespace
	 * 
	 * @param  string $namespace current context namespace
	 * @return string            Latest namespace
	 */
	protected function getParserNamespace($namespace, $context_model, $context_info_key)
	{
		$context_info = $this->getInfo($context_model, $context_info_key);

		if ($context_info) {
			return $namespace . snake_case($context_info['context_relation_name']) . '.';
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

	/**
	 * Check Order
	 * @param  Builder $query     Query Builder
	 * @param  string $namespace Namespace
	 */
	private function checkOrder($query, $namespace) {
		if ($orders = $this->parser->getOption($namespace, 'order')) {
			foreach ($orders as $order) {
				$order_by = $order[0];
				$direction = count($order) > 1 ? $order[1] : 'ASC';
				$query->orderBy($order_by, $direction);
			}
		}
	}

	/**
	 * Check Limit
	 * @param  Builder $query     Query Builder
	 * @param  string $namespace Namespace
	 */
	private function checkLimit($query, $namespace) {
		if ($limit = $this->parser->getOption($namespace, 'limit')) {
			$limit = array_pop($limit);
			if (count($limit) > 0 && is_numeric($limit[0])) {
				$query->take($limit[0]);
			}
		}
	}

	/**
	 * Check Offset
	 * @param  Builder $query     Query Builder
	 * @param  string $namespace Namespace
	 */
	private function checkOffset($query, $namespace) {
		if ($offset = $this->parser->getOption($namespace, 'offset')) {
			$offset = array_pop($offset);
			if (count($offset) > 0 && is_numeric($offset[0])) {
				$query->skip($offset[0]);
			}
		}
	}

	/**
	 * Check Where Clauses
	 * @param  Builder $query     Query Builder
	 * @param  string|null $namespace
	 */
	private function checkWheres($query, $namespace) {
		if ($wheres = $this->parser->getOption($namespace, 'where')) {
			foreach ($wheres as $clause) {
				$field = $clause[0] ?: null;

				if ($field) {
					switch (count($clause)) {
						case '1':
							$value = null;
							break;

						case '2':
							$operator = '=';
							$value = $clause[1];
							break;

						case '3':
						default:
							$operator = $clause[1];
							$value = $clause[2];
							break;
					}

					if (!$value) {
						$query->whereNotNull($field);
					} else {
						$query->where($field, $operator, $value);
					}
				}
			}
		}
	}
}
