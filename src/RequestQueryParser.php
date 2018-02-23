<?php

/**
 * Request Query Parser Class
 *
 * this static class used to prepare and load query
 *
 * @package saad\request-query-loader Laravel Package
 * @author Ahmed Saad <a7mad.sa3d.2014>
 * @version 1.0.0
 * @license [<url>] MIT
 *
 */
namespace Saad\QueryParser;

use Saad\QueryParser\Contracts\QueryParserContract;
use Illuminate\Database\Eloquent\Model;
use Saad\Fractal\FractalRequestParser;

class RequestQueryParser implements QueryParserContract {

	/**
	 * Registerd Models
	 * @var array
	 */
	protected static $registered_models = [];
	
	/**
	 * Register Model Preparer
	 * @param  string   $class_full_name Model Class Full Name
	 * @param  ModelPreparer $registerar_class      Prepare Class
	 * @throws InvalidArgumentException given model class doesnot exists or not Model Class
	 */
	public static function registerModel($class_full_name, $registerar_class) {

		if (!class_exists($class_full_name)) {
			throw new \InvalidArgumentException(__METHOD__ . "class '{$class_full_name}' doesnot exists");
		}

		if (!is_a($class_full_name, \Illuminate\Database\Eloquent\Model::class, true)) {
			throw new \InvalidArgumentException(__METHOD__ . "class '{$class_full_name}' must be a model");
		}

		if (!class_exists($registerar_class)) {
			throw new \InvalidArgumentException(__METHOD__ . "class '{$registerar_class}' doesnot exists");
		}

		self::$registered_models[$class_full_name] = new $registerar_class();
	}

	/**
	 * Prepare Given Model Query
	 * @param  string $class_full_name Full Model Name
	 * @return QueryBuilder                  ModelQueryBuilder
	 */
	public static function prepare($class_full_name) {
		$preparer = self::getPreparerFor($class_full_name);
		return $preparer->prepare();
	}

	/**
	 * Load Model using load Or with on to the given context
	 * @param  string $model_class_full_name Model To load on the given context
	 * @param  Eloquent|Builder $context         Model Eloquent Object Or Model Query Builder of context
	 * @param $relationship_name_of_model_on_context  Relationship method name of the model on the context
	 * @return Eloquent|Builder                  context
	 */
	public static function loadOnContext($model_class_full_name, $context, $namespace = null) {

		$context_class = class_basename($context->getModel());
		$preparer = self::getPreparerFor($model_class_full_name);
		$context_info = $preparer->getInfo($context_class);
		$parser = FractalRequestParser::getInstance();
		$method = self::getLoadingMethod($context);

        if ($parser->includesHas($namespace . $context_info['context_relation_name'])) {
            // Add Context Foreign Key to Select List
            if (isset($context_info['context_foreign'])) {
                $context->addSelect($context_info['context_foreign']);
            }

            // Eager load relation on context
            $context->$method([$context_info['context_relation_name'] => function ($query) use ($preparer, $context_class, $namespace) {
            	$preparer->prepare($query, $context_class, $namespace);
            }]);
        }

        return $context;	
	}

	/**
	 * Get Model Preparer
	 * @param  string $class_full_name model class full name
	 * @return \Closure                  model preparer
	 */
	protected static function getPreparerFor($class_full_name) {
		if (!self::isRegistered($class_full_name)) {
			throw new \RuntimeException(__METHOD__ . " you have to register model preparer for '{$class_full_name}'");
		}

		return self::$registered_models[$class_full_name];
	}

	/**
	 * Check if there are preparer registered for the given model
	 * @param  string  $class_full_name Model full name
	 * @return boolean                  true if registered
	 */
	protected static function isRegistered($class_full_name) {
		return array_key_exists($class_full_name, self::$registered_models);
	}

	/**
	 * Get Context Loading Method
	 * @param  Eloquent|QueryBuilder $context Context
	 * @return string          load OR with
	 */
	protected static function getLoadingMethod($context) {
		if (in_array('Illuminate\Contracts\Support\Jsonable', class_implements($context))) {
            $method = 'load';
        } else {
            $method = 'with';
        }

        return $method;
	}
}