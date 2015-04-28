<?php

namespace ebisbe\solrQueryParser;

class SolrQueryParser
{
    /**
     * @param $conditions
     * @param bool $quoteValues
     * @return array
     */
    public function conditionKeysToString($conditions, $quoteValues = true)
    {
        $out = array();
        $data = $columnType = null;
        $bool = array('and', 'or', 'not', 'and not', 'or not', 'xor', '||', '&&');

        foreach ($conditions as $key => $value) {
            $join = ' AND ';
            $not = null;

            if (is_array($value)) {
                $valueInsert = (
                    !empty($value) &&
                    (substr_count($key, '?') === count($value) || substr_count($key, ':') === count($value))
                );
            }

            if (is_numeric($key) && empty($value)) {
                continue;
            } elseif (is_numeric($key) && is_string($value)) {
                $out[] = $this->_parseKey('', $value);
            } elseif ((is_numeric($key) && is_array($value)) || in_array(strtolower(trim($key)), $bool)) {
                if (in_array(strtolower(trim($key)), $bool)) {
                    $join = ' ' . strtoupper($key) . ' ';
                } else {
                    $key = $join;
                }
                $value = $this->conditionKeysToString($value, $quoteValues);

                if (strpos($join, 'NOT') !== false) {
                    if (strtoupper(trim($key)) === 'NOT') {
                        $key = 'AND ' . trim($key);
                    }
                    $not = 'NOT ';
                }

                if (empty($value)) {
                    continue;
                }

                if (empty($value[1])) {
                    if ($not) {
                        $out[] = $not . '(' . $value[0] . ')';
                    } else {
                        $out[] = $value[0];
                    }
                } else {
                    $out[] = '(' . $not . '(' . implode(') ' . strtoupper($key) . ' (', $value) . '))';
                }
            } else {
                if (is_object($value) && isset($value->type)) {
                    if ($value->type === 'identifier') {
                        $data .= $this->name($key) . ' = ' . $this->name($value->value);
                    } elseif ($value->type === 'expression') {
                        if (is_numeric($key)) {
                            $data .= $value->value;
                        } else {
                            $data .= $this->name($key) . ' = ' . $value->value;
                        }
                    }
                } elseif (is_array($value) && !empty($value) && !$valueInsert) {
                    $keys = array_keys($value);
                    if ($keys === array_values($keys)) {
                        $count = count($value);
                        if ($count === 1 && !preg_match('/\s+(?:NOT|\!=)$/', $key)) {
                            $data = $this->_quoteFields($key) . ' = (';
                            if ($quoteValues) {
                                if ($Model !== null) {
                                    $columnType = $Model->getColumnType($key);
                                }
                                $data .= implode(', ', $this->value($value, $columnType));
                            }
                            $data .= ')';
                        } else {
                            $data = $this->_parseKey($key, $value);
                        }
                    } else {
                        $ret = $this->conditionKeysToString($value, $quoteValues);
                        if (count($ret) > 1) {
                            $data = '(' . implode(') AND (', $ret) . ')';
                        } elseif (isset($ret[0])) {
                            $data = $ret[0];
                        }
                    }
                } elseif (is_numeric($key) && !empty($value)) {
                    $data = $this->_parseKey('', $value);
                } else {
                    $data = $this->_parseKey(trim($key), $value);
                }

                if ($data) {
                    $out[] = $data;
                    $data = null;
                }
            }
        }

        return $out;
    }

    private function _parseKey($key = '', $value)
    {
        if(!empty($key)) {
            $key .= ': ';
        }
        if(is_array($value)) {
            $sql = '';
        } else {
            return $key.$value;
        }
    }
}