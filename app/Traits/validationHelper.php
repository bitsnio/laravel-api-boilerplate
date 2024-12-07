<?php

namespace App\Traits;

trait validationHelper
{
	public static function validationErrorsToString($errArray)
	{
		$valArr = array();
		foreach ($errArray->toArray() as $key => $value) {
			$errStr = $key . ':' . $value[0];
			array_push($valArr, $errStr);
		}
		if (!empty($valArr)) {
			$errStrFinal = implode('<br>', $valArr);
		}
		return $errStrFinal;
	}
}
