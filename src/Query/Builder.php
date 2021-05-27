<?php

declare(strict_types=1);
/**
 * This file is part of elasticsearch.
 *
 * @link     https://github.com/friendsofhyperf/elasticsearch
 * @document https://github.com/friendsofhyperf/elasticsearch/blob/main/README.md
 * @contact  hdj@addcn.com
 */
namespace FriendsOfHyperf\Elasticsearch\Query;

use Closure;
use InvalidArgumentException;

class Builder
{
    /**
     * 索引名.
     *
     * @var string
     */
    protected $index;

    /**
     * 索引Type.
     *
     * @var string
     */
    protected $type;

    /**
     * 搜寻条件.
     *
     * @var array
     */
    protected $wheres = [
        'filter' => [],
        'should' => [],
        'must' => [],
        'must_not' => [],
    ];

    /**
     * 排序.
     *
     * @var array
     */
    protected $sort = [];

    /**
     * 从X条开始查询.
     *
     * @var int
     */
    protected $from;

    /**
     * 获取数量.
     *
     * @var int
     */
    protected $size;

    /**
     * 需要查询的字段.
     *
     * @var array
     */
    protected $_source;

    /**
     * 聚合查询条件.
     *
     * @var array
     */
    protected $aggs = [];

    /**
     * collapse.
     * @var array
     */
    protected $collapse;

    /**
     * highlight.
     * @var array
     */
    protected $highlightFields = [];

    /**
     * script_fields.
     * @var array
     */
    protected $scriptFields = [];

    /**
     * @var array
     */
    protected $script;

    /**
     * 所有的区间查询配置.
     *
     * @var array
     */
    protected $rangeOperators = [
        '>' => 'gt', '<' => 'lt', '>=' => 'gte', '<=' => 'lte',
    ];

    /**
     * 实例化一个构建链接.
     */
    public function __construct(?string $index = '')
    {
        $this->index = $index;
    }

    /**
     * 指定索引名.
     *
     * @param string $value
     *
     * @return $this
     */
    public function index($value)
    {
        $this->index = $value;

        return $this;
    }

    /**
     * 指定type.
     *
     * @param string $value
     *
     * @return $this
     */
    public function type($value)
    {
        $this->type = $value;

        return $this;
    }

    /**
     * 指定需要查询获取的字段.
     *
     * @param array|mixed $columns
     *
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $this->_source = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    /**
     * add select.
     * @param array $columns
     * @return $this
     */
    public function addSelect($columns = [])
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $this->_source += $columns;

        return $this;
    }

    /**
     * 添加别名.
     * @param mixed $column
     * @param mixed $alias
     * @return $this
     */
    public function addAlias($column, $alias)
    {
        return $this->addScriptField($column, $alias);
    }

    /**
     * 别名.
     * @param string $column
     * @param string $alias
     * @return $this
     */
    public function addScriptField($column, $alias)
    {
        if ($column && $alias) {
            $this->scriptFields[$alias] = [
                'script' => [
                    'source' => "doc['{$column}'].value",
                    'lang' => 'painless',
                ],
                'ignore_failure' => false,
            ];
        }

        return $this;
    }

    /**
     * 追加排序规则.
     *
     * @param mixed $value
     * @param mixed $prepend
     * @param bool $prepend
     *
     * @return $this
     */
    public function addOrder($value, $prepend = false)
    {
        if ($prepend) {
            $this->sort = array_merge($value, $this->sort);
        } else {
            $this->sort = array_merge($this->sort, $value);
        }

        return $this;
    }

    /**
     * 按自定字段排序.
     *
     * @param string $column
     * @param string $direction
     * @param bool $prepend
     *
     * @return $this
     */
    public function orderBy($column, $direction = 'asc', $prepend = false)
    {
        return $this->addOrder([
            $column => strtolower($direction) == 'asc' ? 'asc' : 'desc',
        ], $prepend);
    }

    /**
     * offset 方法别名.
     *
     * @param int $value
     *
     * @return $this
     */
    public function skip($value)
    {
        return $this->offset($value);
    }

    /**
     * 跳过X条数据.
     *
     * @param int $value
     *
     * @return $this
     */
    public function offset($value)
    {
        if ($value >= 0) {
            $this->from = $value;
        }

        return $this;
    }

    /**
     * 聚合.
     * @param string $column
     * @param array $innerHitsOpts
     * @param int $maxConcurrentGroupSearches
     * @return $this
     */
    public function collapse($column, $innerHitsOpts = [], $maxConcurrentGroupSearches = 5)
    {
        $innerHitsOpts = array_merge([
            'name' => 'items',
            'size' => 5,
            'sort' => [
                ['id' => 'desc'],
            ],
        ], $innerHitsOpts);

        $this->collapse = [
            'field' => $column,
            'inner_hits' => $innerHitsOpts,
            'max_concurrent_group_searches' => $maxConcurrentGroupSearches,
        ];

        return $this;
    }

    /**
     * 高亮字段.
     * @param string $column
     * @param string $preTag
     * @param string $postTag
     * @return $this
     */
    public function highlight($column, $preTag = '<em>', $postTag = '</em>')
    {
        $this->highlightFields[$column] = [
            'pre_tags' => (array) $preTag,
            'post_tags' => (array) $postTag,
        ];

        return $this;
    }

    /**
     * limit 方法别名.
     *
     * @param int $value
     *
     * @return $this
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * 设置获取的数据量.
     *
     * @param int $value
     *
     * @return $this
     */
    public function limit($value)
    {
        if ($value >= 0) {
            $this->size = $value;
        }

        return $this;
    }

    /**
     * 以分页形式获取指定数量数据.
     *
     * @param int $page
     * @param int $perPage
     *
     * @return $this
     */
    public function forPage($page, $perPage = 15)
    {
        return $this->skip(($page - 1) * $perPage)->take($perPage);
    }

    /**
     * 返回新的构建类.
     *
     * @return \Flc\Laravel\Elasticsearch\Query\Builder
     */
    public function newQuery()
    {
        return new static($this->index);
    }

    /**
     * 增加一个条件到查询中.
     *
     * @param mixed $value 条件语法
     * @param string $type 条件类型，filter/must/must_not/should
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function addWhere($value, $type = 'filter')
    {
        if (! array_key_exists($type, $this->wheres)) {
            throw new InvalidArgumentException("Invalid where type: {$type}.");
        }

        $this->wheres[$type][] = $value;

        return $this;
    }

    /**
     * term 查询.
     *
     * @param string $column 字段
     * @param mixed $value 值
     * @param string $type 条件类型
     *
     * @return $this
     */
    public function whereTerm($column, $value, $type = 'filter')
    {
        return $this->addWhere(
            ['term' => [$column => $value]],
            $type
        );
    }

    /**
     * terms 查询.
     *
     * @param string $column 字段
     * @param array $value 值
     * @param string $type 条件类型
     *
     * @return $this
     */
    public function whereTerms($column, array $value, $type = 'filter')
    {
        return $this->addWhere(
            ['terms' => [$column => $value]],
            $type
        );
    }

    /**
     * match 查询.
     *
     * @param string $column 字段
     * @param mixed $value 值
     * @param string $type 条件类型
     *
     * @return $this
     */
    public function whereMatch($column, $value, $type = 'filter')
    {
        return $this->addWhere(
            ['match' => [$column => $value]],
            $type
        );
    }

    /**
     * match_phrase 查询.
     *
     * @param string $column 字段
     * @param mixed $value 值
     * @param string $type 条件类型
     *
     * @return $this
     */
    public function whereMatchPhrase($column, $value, $type = 'filter')
    {
        return $this->addWhere(
            ['match_phrase' => [$column => $value]],
            $type
        );
    }

    /**
     * match_phrase 查询.
     * @param array $columns
     * @param mixed $value
     * @param array $options
     * @param string $type
     * @throws InvalidArgumentException
     * @return $this
     */
    public function whereMultiMatch($columns = [], $value, $options = [], $type = 'filter')
    {
        $columns = (array) $columns;
        $options = (array) $options;

        return $this->addWhere(
            [
                'multi_match' => [
                    'query' => $value,
                    'fields' => $columns,
                    // 'analyzer' => 'ik_max_word',
                    // 'operator' => 'and',
                ] + $options,
            ],
            $type
        );
    }

    /**
     * range 查询.
     *
     * @param string $column 字段
     * @param string $operator 查询符号
     * @param mixed $value 值
     * @param string $type 条件类型
     *
     * @return $this
     */
    public function whereRange($column, $operator, $value, $type = 'filter')
    {
        if (! array_key_exists($operator, $this->rangeOperators)) {
            throw new InvalidArgumentException("Invalid operator: {$operator}.");
        }

        return $this->addWhere([
            'range' => [
                $column => [$this->rangeOperators[$operator] => $value],
            ],
        ], $type);
    }

    /**
     * 区间查询(含等于).
     *
     * @param string $column 字段
     * @param array $value 区间值
     * @param string $type 条件类型
     *
     * @return $this
     */
    public function whereBetween($column, array $value = [], $type = 'filter')
    {
        return $this->addWhere([
            'range' => [
                $column => [
                    'gte' => $value[0],
                    'lte' => $value[1],
                ],
            ],
        ], $type);
    }

    /**
     * 不在区间.
     *
     * @param string $column
     * @param string $type
     * @throws InvalidArgumentException
     * @return $this
     */
    public function whereNotBetween($column, array $value = [], $type = 'must_not')
    {
        return $this->whereBetween($column, $value, 'must_not');
    }

    /**
     * 字段非 null 查询.
     *
     * @param string $column
     * @param string $type
     *
     * @return $this
     */
    public function whereExists($column, $type = 'filter')
    {
        return $this->addWhere([
            'exists' => ['field' => $column],
        ], $type);
    }

    /**
     * 查询字段为 null.
     *
     * @param string $column
     *
     * @return $this
     */
    public function whereNotExists($column)
    {
        return $this->whereExists($column, 'must_not');
    }

    /**
     * whereNotExists 别名.
     *
     * @param string $column
     *
     * @return $this
     */
    public function whereNull($column)
    {
        return $this->whereNotExists($column);
    }

    /**
     * where 条件查询.
     *
     * @param array|Colsure|string $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $type
     *
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $type = 'filter')
    {
        // 如果是数组
        if (is_array($column)) {
            return $this->addArrayOfWheres($column, $type);
        }

        // 如果 column 是匿名函数
        if ($column instanceof Closure) {
            return $this->whereNested(
                $column,
                $type
            );
        }

        // 如果只有两个参数
        if (func_num_args() === 2) {
            [$value, $operator] = [$operator, '='];
        }

        // 符号查询
        $this->performWhere($column, $value, $operator, $type);

        return $this;
    }

    /**
     * or where 查询(whereShould 别名).
     *
     * @param array|Closure|string $column
     * @param mixed $operator
     * @param mixed $value
     *
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            [$value, $operator] = [$operator, '='];
        }

        return $this->where($column, $operator, $value, 'should');
    }

    /**
     * 多条件 and 查询；whereTerms 别名.
     *
     * @param string $column 字段
     * @param array $value 值
     * @param string $type 条件类型
     *
     * @return $this
     */
    public function whereIn($column, array $value, $type = 'filter')
    {
        return $this->whereTerms($column, $value, $type);
    }

    /**
     * 多条件 OR 查询.
     *
     * @param string $column
     *
     * @return $this
     */
    public function orWhereIn($column, array $value)
    {
        return $this->whereIn($column, $value, 'should');
    }

    /**
     * 多条件反查询；反whereIn.
     *
     * @param string $column 字段
     * @param array $value 值
     *
     * @return $this
     */
    public function whereNotIn($column, array $value)
    {
        return $this->whereIn($column, $value, 'must_not');
    }

    /**
     * 嵌套查询.
     *
     * @param Closure $callback 回调函数
     * @param string $type 条件类型
     *
     * @return $this
     */
    public function whereNested(Closure $callback, $type = 'filter')
    {
        call_user_func($callback, $query = $this->forNestedWhere());

        return $this->addNestedWhereQuery($query, $type);
    }

    /**
     * 创建一个用户嵌套查询的构建实例.
     *
     * @return Builder
     */
    public function forNestedWhere()
    {
        return $this->newQuery();
    }

    /**
     * 将嵌套的查询构建条件加入到查询中.
     *
     * @param Builder $query
     * @param string $type
     */
    public function addNestedWhereQuery(self $query, $type = 'filter')
    {
        if ($bool = $query->compileWheres($query)) {
            $this->addWhere(
                ['bool' => $bool],
                $type
            );
        }

        return $this;
    }

    /**
     * 脚本.
     *
     * @return $this
     */
    public function script(array $payload)
    {
        $this->script = $payload;

        return $this;
    }

    /**
     * 返回查询的 body 参数.
     *
     * @return array
     */
    public function compileBody()
    {
        $body = [];

        if (count($this->sort) > 0) {
            $body['sort'] = $this->sort;
        }

        if ($bool = $this->compileWheres()) {
            $body['query']['bool'] = $bool;
        }

        if ($this->collapse) {
            $body['collapse'] = $this->collapse;
        }

        if ($this->highlightFields) {
            $body['highlight'] = ['fields' => $this->highlightFields];
        }

        if (count($this->scriptFields) > 0) {
            $body['script_fields'] = $this->scriptFields;
        }

        if (count($this->aggs) > 0) {
            $body['aggs'] = $this->aggs;
        }

        return $body;
    }

    /**
     * 返回基础公共参数.
     *
     * @return array
     */
    public function compileBase()
    {
        $params = [];

        $params['index'] = $this->index;

        if (! is_null($this->type)) {
            $params['type'] = $this->type;
        }

        return $params;
    }

    /**
     * 转换 where 条件.
     *
     * @return array
     */
    public function compileWheres()
    {
        $wheres = [];

        foreach ($this->wheres as $type => $where) {
            if ($where) {
                $wheres[$type] = $where;
            }
        }

        return $wheres;
    }

    /**
     * 转换脚本.
     *
     * @return array
     */
    public function compileScript()
    {
        return $this->script;
    }

    /**
     * 返回搜索的参数.
     *
     * @return array
     */
    public function compileSearch()
    {
        $params = $this->compileBase();

        if (! is_null($this->_source)) {
            $_source = [];

            foreach ($this->_source as $source) {
                if (stripos($source, ' as ')) {
                    $sources = preg_split('/\s+as\s+/', $source);
                    $this->addScriptField($sources[0], $sources[1] ?? '');
                    $_source[] = $sources[0];
                }
            }

            $params['_source'] = $_source;
        } else {
            $params['_source'] = [
                'includes' => [],
                'excludes' => [],
            ];
        }

        if (! is_null($this->from)) {
            $params['from'] = $this->from;
        }

        if (! is_null($this->size)) {
            $params['size'] = $this->size;
        }

        if ($body = $this->compileBody()) {
            $params['body'] = $body;
        }

        if ($script = $this->compileScript()) {
            if (! isset($params['body'])) {
                $params['body'] = [];
            }

            $params['body']['script'] = $script;
        }

        return $params;
    }

    /**
     * Apply the callback's query changes if the given "value" is true.
     *
     * @param callable $callback
     * @param callable $default
     * @param mixed $value
     * @return $this|mixed
     */
    public function when($value, $callback, $default = null)
    {
        if ($value) {
            return $callback($this, $value) ?: $this;
        }
        if ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * Pass the query to a given callback.
     *
     * @param \Closure $callback
     * @return $this|mixed
     */
    public function tap($callback)
    {
        return $this->when(true, $callback);
    }

    /**
     * Apply the callback's query changes if the given "value" is false.
     *
     * @param callable $callback
     * @param callable $default
     * @param mixed $value
     * @return $this|mixed
     */
    public function unless($value, $callback, $default = null)
    {
        if (! $value) {
            return $callback($this, $value) ?: $this;
        }
        if ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * 聚合.
     * @param mixed $column
     * @param string $type
     * @param string $alias
     * @return $this
     */
    public function aggregation($column, $type = 'cardinality', $alias = '')
    {
        $alias = $alias ?: $column;

        $this->aggs[$alias] = [
            $type => [
                'field' => $column,
            ],
        ];

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->compileSearch();
    }

    /**
     * 添加一个数组条件的查询.
     *
     * @param array $column
     * @param string $type
     * @param string $method
     *
     * @return $this
     */
    protected function addArrayOfWheres($column, $type, $method = 'where')
    {
        return $this->whereNested(function ($query) use ($column, $method, $type) {
            foreach ($column as $key => $value) {
                if (is_array($value)) {
                    $query->{$method}(...$value);
                } else {
                    $query->{$method}($key, '=', $value, $type);
                }
            }
        });
    }

    /**
     * 处理符号搜索.
     *
     * @param string $column 字段
     * @param mixed $value 值
     * @param string $operator 符号
     * @param string $type 条件类型
     *
     * @return array
     */
    protected function performWhere($column, $value, $operator, $type = 'filter')
    {
        switch ($operator) {
            case '=':
                return $this->whereTerm($column, $value, $type);
                break;
            case '>':
            case '<':
            case '>=':
            case '<=':
                return $this->whereRange($column, $operator, $value, $type);
                break;
            case '!=':
            case '<>':
                return $this->whereTerm($column, $value, 'must_not');
                break;
            case 'match':
                return $this->whereMatch($column, $value, $type);
                break;
            case 'not match':
            case 'notmatch':
                return $this->whereMatch($column, $value, 'must_not');
                break;
            case 'like':
                return $this->whereMatchPhrase($column, $value, $type);
                break;
            case 'not like':
            case 'notlike':
                return $this->whereMatchPhrase($column, $value, 'must_not');
            break;
        }
    }
}
