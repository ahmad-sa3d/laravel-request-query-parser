<?php

/**
 * Model Preparer Contract
 *
 * @package saad\request-query-loader Laravel Package
 * @author Ahmed Saad <a7mad.sa3d.2014>
 * @version 1.0.0
 * @license [<url>] MIT
 */
namespace Saad\QueryParser\Contracts;

interface ModelPreparerContract {
	
	/**
	 * Register Model Preparer
	 * 
	 * @param  string   $class_full_name Model Class Full Name
	 */
	public function prepare($context);

	/**
	 * Get Prepare Relation Info for the given model name
	 * 
	 * @param  string $model Model Class Name
	 * @return array         relation info or empty array
	 */
	public function getInfo($model);
}