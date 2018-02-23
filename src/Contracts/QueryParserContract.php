<?php

/**
 * Query Parser Contract
 *
 * @package saad\request-query-loader Laravel Package
 * @author Ahmed Saad <a7mad.sa3d.2014>
 * @version 1.0.0
 * @license [<url>] MIT
 */

namespace Saad\QueryParser\Contracts;

interface QueryParserContract {
	
	/**
	 * Register Model Preparer
	 * 
	 * @param  string   $class_full_name 	Model Class Full Name
	 * @param  string 	$registerar      	Prepare Class Full Name
	 */
	public static function registerModel($class_full_name, $registerar);


	/**
	 * Prepare Given Model Query
	 * 
	 * @param  string $class_full_name 	Full Model Name
	 * @return Builder                  Model Query Builder
	 */
	public static function prepare($class_full_name);


	/**
	 * Load Model using load Or with on to the given context
	 * 
	 * @param  string 			$model_class_full_name 		Model To load on the given context
	 * @param  Eloquent|Builder $context         			Model Eloquent Object Or Model QueryBuilder of context
	 * @param  string 			$namespace  				current namespace prefix to add to fractal request parser
	 * @return Eloquent|Builder                  			given context after prepared
	 */
	public static function loadOnContext($model_class_full_name, $context, $namespace);
}