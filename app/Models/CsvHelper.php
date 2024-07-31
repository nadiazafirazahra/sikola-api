<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class CsvHelper extends Model {

	//
	public static function csv_to_array($csv_file){
		$file = $csv_file;
		$row = 1;
		$result="ALLIQ";
		if (($handle = fopen($file, "r")) !== FALSE) {
			$result=[];
			while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
				$result[] = $data[0];
			}
			fclose($handle);
			return $result;
		}
		return null;
	}


}
