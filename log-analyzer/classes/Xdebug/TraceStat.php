<?php
/**
 * User: yuriy
 * Date: 06.09.13 12:53
 */

class Xdebug_TraceStat
{
	public static function load()
	{
		return new self();
	}

	public function __construct()
	{
	}

	public function getTracesList()
	{
		$db = db::get();
		$list = $db->fetchAll("SELECT * FROM xdebug_trace_sessions");

		foreach ($list as $i => $item)
			$list[$i] = $this->_prepareSessData($item);

		return $list;
	}

	public function getFirstLevelCalls($sessId)
	{
		$db = db::get();
		$sessData = $this->getSessData($sessId);
		$calls = $db->fetchAll("SELECT * FROM xdebug_trace WHERE sess_id=? AND level=1 ORDER BY call_index", $sessId);

		return array(
			'calls' => $this->_prepareCalls($calls, $sessData),
			'sessData' => $sessData,
		);
	}

	public function getSessData($sessId)
	{
		$db = db::get();
		$sessData = $db->fetchRow("SELECT * FROM xdebug_trace_sessions WHERE id=?", $sessId);

		if (!$sessData)
			throw new Exception("Session $sessId not found");

		$sessData = $this->_prepareSessData($sessData);
		return $sessData;
	}

	public function getFuncChildren($sessId, $funcId)
	{
		$db = db::get();
		$sessData = $this->getSessData($sessId);
		$calls = $db->fetchAll("
			SELECT * FROM xdebug_trace WHERE sess_id=? AND parent_func_id=? ORDER BY call_index
		", array($sessId, $funcId));

		$calls = $this->_prepareCalls($calls, $sessData);
		return $calls;
	}

	public function getFuncDetails($callId)
	{
		$db = db::get();
		$funcData = $db->fetchRow("SELECT * FROM xdebug_trace WHERE id=?", $callId);
		if (!$funcData)
			throw new Exception('function call not found');

		return $funcData;
	}

	protected function _prepareSessData($row)
	{
		$output = $row;
		$output['total_memory_str'] = self::formatMemory($row['total_memory']);
		$output['total_time_str'] = self::formatTime($row['total_time']);

		return $output;
	}

	protected function _prepareCalls($calls, $sessData)
	{
		$maxNestedCalls = 0;
		$maxMemory = 0;
		$maxTime = 0;
		foreach ($calls as & $call) {
			$call = $this->_prepareCallData($call, $sessData);
			$maxNestedCalls = max($maxNestedCalls, $call['num_nested_calls']);
			$maxMemory = max($maxMemory, $call['mem_diff']);
			$maxTime = max($maxTime, $call['time_diff']);
		} unset($call);

		foreach ($calls as & $call) {
			if ($call['num_nested_calls'] == $maxNestedCalls) $call['max_calls'] = true;
			if ($call['mem_diff'] == $maxMemory) $call['max_mem'] = true;
			if ($call['time_diff'] == $maxTime) $call['max_time'] = true;
		} unset($call);

		return $calls;
	}

	protected function _prepareCallData($row, $sessData)
	{
		$output = array();
		$keys = array('id', 'level', 'call_index', 'func_name', 'call_file', 'call_line', 'parent_func_id', 'num_nested_calls');
		foreach ($keys as $key)
			$output[$key] = $row[$key];

		$argsLen = strlen($row['args']);

		if (!empty($row['included_file'])) {
			$output['args_str'] = "'".str_replace($sessData['app_base_path'], '', $row['included_file'])."'";
		} elseif (!$row['num_args']) {
			$output['args_str'] = '';
		} elseif ($argsLen < 150) {
			$output['args_str'] = implode(', ', explode("\t", $row['args'], $row['num_args']));
		} else {
			$output['args_str'] = '...';
		}

		$output['time_diff'] = sprintf('%.3f', $row['time_end'] - $row['time_start']);
		$output['mem_diff'] = $row['memory_end'] - $row['memory_start'];
		$output['mem_diff_str'] = self::formatMemory($output['mem_diff']);
		$output['max_mem'] = false;
		$output['max_time'] = false;
		$output['max_calls'] = false;

		return $output;
	}

	public static function formatMemory($bytesize, $toArray = FALSE)
	{
		if($bytesize < 1048576)
			$output = array('value' => round($bytesize / 1024, 2), 'units' => 'kB');
		elseif($bytesize < 1073741824)
			$output = array('value' => round($bytesize / 1048576, 2), 'units' => 'MB');
		else
			$output = array('value' => round($bytesize / 1073741824, 2), 'units' => 'GB');

		return $toArray
			? $output
			: $output['value'].' '.$output['units'];
	}

	public static function formatTime($time)
	{
		return sprintf('%.3f sec', $time);
	}

}