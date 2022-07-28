<?php

namespace SearchRegex\Sql;

/**
 * SQL Query class
 */
class Query {
	/**
	 * Array of Where\Where objects
	 *
	 * @var array
	 */
	private $where = [];

	/**
	 * Array of Select\Select objects
	 *
	 * @var Select\Select[]
	 */
	private $select = [];

	/**
	 * Array of From objects
	 *
	 * @var From[]
	 */
	private $from = [];

	/**
	 * Array of group objects
	 *
	 * @var Group[]
	 */
	private $group = [];

	/**
	 * Array of joins
	 *
	 * @var Join\Join[]
	 */
	private $joins = [];

	/**
	 * Current page offset
	 *
	 * @var integer|null
	 */
	private $offset = null;

	/**
	 * Query limit
	 *
	 * @var integer|null
	 */
	private $limit = null;

	/**
	 * Query order
	 *
	 * @var string|null
	 */
	private $order = null;

	/**
	 * Query order direction
	 *
	 * @var String
	 */
	private $order_direction = 'ASC';

	/**
	 * Set the query order
	 *
	 * @param String       $order Column to order on.
	 * @param 'ASC'|'DESC' $order_direction Direction of ordering.
	 * @return void
	 */
	public function set_order( $order, $order_direction = 'ASC' ) {
		$order_direction = strtoupper( $order_direction );
		$this->order = $order;

		if ( in_array( $order_direction, [ 'ASC', 'DESC' ], true ) ) {
			$this->order_direction = $order_direction;
		}
	}

	/**
	 * Set the query page parameters.
	 *
	 * @param integer $offset Current offset.
	 * @param integer $limit Current limit.
	 * @return void
	 */
	public function set_paging( $offset, $limit ) {
		$this->offset = intval( $offset, 10 );
		$this->limit = intval( $limit, 10 );
	}

	/**
	 * Add Where\Where to the query
	 *
	 * @param Where\Where $where Where\Where.
	 * @return void
	 */
	public function add_where( Where\Where $where ) {
		$this->where[] = $where;
	}

	/**
	 * Reset all the Where\Where objects
	 *
	 * @return void
	 */
	public function reset_where() {
		$this->where = [];
	}

	/**
	 * Get the Where\Where objects
	 *
	 * @return list<Where\Where>
	 */
	public function get_where() {
		return $this->where;
	}

	/**
	 * Add Select\Select to query
	 *
	 * @param Select\Select $select Select.
	 * @return void
	 */
	public function add_select( Select\Select $select ) {
		$this->select[] = $select;
	}

	/**
	 * Add array of Select\Select objects
	 *
	 * @param Select\Select[] $selects Selects.
	 * @return void
	 */
	public function add_selects( array $selects ) {
		$this->select = array_merge( $this->select, $selects );
	}

	/**
	 * Add From to query.
	 *
	 * @param From $from From.
	 * @return void
	 */
	public function add_from( From $from ) {
		$this->from[] = $from;
	}

	/**
	 * Add Join to query.
	 *
	 * @param Join\Join $join Join.
	 * @return void
	 */
	public function add_join( Join\Join $join ) {
		$this->joins[] = $join;
	}

	/**
	 * Add Group to query
	 *
	 * @param Group $group Group.
	 * @return void
	 */
	public function add_group( Group $group ) {
		$this->group[] = $group;
	}

	/**
	 * Add the selects from another query
	 *
	 * @param Query $query Query.
	 * @return void
	 */
	public function add_select_only( Query $query ) {
		$this->select = array_merge( $this->select, $query->select );
	}

	/**
	 * Add everything from a query except the WHERE, and return the WHERE
	 *
	 * @param Query $query Query.
	 * @return Where\Where[]
	 */
	public function add_query_except_where( Query $query ) {
		$this->select = array_merge( $this->select, $query->select );
		$this->from = array_merge( $this->from, $query->from );
		$this->group = array_merge( $this->group, $query->group );
		$this->joins = array_merge( $this->joins, $query->joins );
		return $query->where;
	}

	/**
	 * Add another query
	 *
	 * @param Query $query Query.
	 * @return void
	 */
	public function add_query( Query $query ) {
		$this->where = array_merge( $this->where, $query->where );
		$this->select = array_merge( $this->select, $query->select );
		$this->from = array_merge( $this->from, $query->from );
		$this->group = array_merge( $this->group, $query->group );
		$this->joins = array_merge( $this->joins, $query->joins );
	}

	/**
	 * Get the query as a SQL statement
	 *
	 * @param Modifier\Modifier|null $modifier Modifier for the query.
	 * @return string
	 */
	public function get_as_sql( Modifier\Modifier $modifier = null ) {
		global $wpdb;

		if ( $modifier === null ) {
			$modifier = new Modifier\Modifier();
		}

		$select = array_unique( $modifier->get_select( $this->select, $this->joins ) );
		$from = array_unique( $modifier->get_from( $this->from, $this->joins ) );
		$where = $modifier->get_where( $this->where, $this->joins );
		$group = array_unique( $modifier->get_group( $this->group, $this->joins ) );

		// SELECT select FROM from joins WHERE where group order limit
		$sql = [
			'SELECT',
			implode( ', ', array_unique( $select ) ),
			'FROM',
			implode( ' ', array_unique( $from ) ),
		];

		$sql = array_merge( $sql, $this->get_query_section( 'WHERE', $where, ' AND ' ) );
		$sql = array_merge( $sql, $this->get_query_section( 'GROUP BY', $group ) );

		if ( $this->order ) {
			$sql[] = 'ORDER BY';
			$sql[] = $this->order;
			$sql[] = $this->order_direction;
		}

		if ( $this->offset !== null ) {
			$sql[] = $wpdb->prepare( 'LIMIT %d,%d', $this->offset, $this->limit );
		}

		return implode( ' ', $sql );
	}

	/**
	 * Get a group of SQL statements with a space between them
	 *
	 * @param string $label SQL label.
	 * @param array  $values Group of statements.
	 * @param string $separator Seperator for statements.
	 * @return array<string>
	 */
	private function get_query_section( $label, array $values, $separator = ' ' ) {
		if ( count( $values ) > 0 ) {
			return [ $label . ' ' . implode( $separator, $values ) ];
		}

		return [];
	}
}
