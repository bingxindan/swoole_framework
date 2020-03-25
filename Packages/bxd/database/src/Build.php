<?php

namespace BxdFramework\DataBase;

class Build
{

	const COND_OPT = ['=', '!=', '<>', '>', '>=', '<', '<=', 'IN', 'NOT IN'];

    /**
     * 补充特殊字符'`'
     * SELECT *,`a`,`b` FROM d_test
     */
    static public function fieldStr ($data) : String
    {
        $str = '*';
        if(!$data) return $str;

        $data = is_array($data) ? $data : explode(',', $data);
        foreach($data as $key => $item) {
            $item = trim($item);
            $data[$key] = ctype_alnum($item) ? "`$item`" : $item;
        }

        $str = implode(', ', $data);
        return $str;
    }

    /**
     * 生成分页stat参数
     * SELECT * FROM d_test WHERE 1 LIMIT :l0, :l1;
     */
    static public function limitParams ($data)
    {
    	if (empty($data)) return ['', [], []];
    	$str = ' LIMIT ' . $data;
        return [$str, [], []];
    }

    /**
     * 生成排序stat参数
     * SELECT * FROM d_test WHERE 1 ORDER BY :o0, :o1;
     */
    static public function orderParams ($data)
    {
    	if (!$data) return ['', [], []];
        $str = ' ORDER BY ' . $data;
        return [$str, [], []];
    }

    /**
     * 生成更新值stat参数
     * UPDATE d_test SET a = :u0, b = :u1 WHERE 1;
     */
    static public function updateParams ($data)
    {
        $cnt = 0;
        $str = '';
        $arr = [];
        $params = [];
        $values = [];
        if(!is_array($data)) return [$data, $params, $values];

        foreach($data as $column => $setting) {
            if(is_array($setting)) {
                foreach($setting as $key => $item) {
                    if(!in_array($key, self::COND_OPT)) continue;
                    $p = sprintf(":u%d", $cnt++);
                    $params[$p] = $column;
                    $values[$p] = $item;
                    $arr[] = $key === "=" ? sprintf("`%s` = %s", $column, $p) : sprintf("`%s` = `%s` %s %s", $column, $column, $key, $p);
                }
            } else {
                $p = sprintf(":u%d", $cnt++);
                $params[$p] = $column;
                $values[$p] = $setting;
                $arr[] = sprintf("`%s` = %s", $column, $p);
            }
        }
        $str = implode(', ', $arr);

        return [$str, $params, $values];
    }

    /**
     * 生成条件stat参数
     * SELECT * FROM d_test WHERE a = :w0 AND b = :w1
     * UPDATE d_test SET ... WHERE a = :w0 AND b = :w1;
     * DELETE FROM d_test WHERE a = :w0 AND b = :w1;
     */
    static public function whereParams($data)
    {
        $cnt = 0;
        $str = '';
        $arr = [];
        $params = [];
        $values = [];
        if (!is_array($data)) {
            return [$data, $params, $values];
        }

        foreach ($data as $column => $condition) {
            if (is_array($condition)) {
                $opt = isset($condition[0]) ? strtoupper($condition[0]) : null;
                $val = $condition[1] ?? null;
                if ($opt === null || $val === null) continue;
                if (is_array($val)) {
                    $pArr = [];
                    foreach ($val as $v) {
                        $p = sprintf(":w%d", $cnt++);
                        $pArr[] = $p;
                        $params[$p] = $column;
                        $values[$p] = $v;
                    }
                    $arr[] = sprintf("`%s` %s (%s)", $column, $opt, implode(', ', $pArr));
                } else {
                    $p = sprintf(":w%d", $cnt++);
                    $params[$p] = $column;
                    $values[$p] = $val;
                    $arr[] = sprintf("`%s` %s %s", $column, $opt, $p);
                }
            } else {
                $p = sprintf(":w%d", $cnt++);
                $params[$p] = $column;
                $values[$p] = $condition;
                $arr[] = sprintf("`%s` = %s", $column, $p);
            }
        }

        $str = implode(' AND ', $arr);
        return [$str, $params, $values];
    }

    /**
     * 补充特殊字符'`'
     * SELECT *,`a`,`b` FROM d_test
     */
    static public function insertParams ($data)
    {
    	$fields = [];
    	$params = [];
    	$values = [];
    	foreach ($data as $field => $value) {
    		$param = sprintf(':%s', $field);
    		$fields[] = $field;
    		$params[$param] = $field;
    		$values[$param] = $value;
    	}
    	return [$fields, $params, $values];
    }

}
