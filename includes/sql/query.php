<?php

namespace SearchRegex\Sql;

/**
 * SQL Query class
 */
class Sql_Query {
	/**
	 * Array of Sql_Where objects
	 *
	 * @var array
	 */
	private $where = [];

	/**
	 * Array of Sql_Select objects
	 *
	 * @var Sql_Select[]
	 */
	private $select = [];

	/**
	 * Array of Sql_From objects
	 *
	 * @var Sql_From[]
	 */
	private $from = [];

	/**
	 * Array of group objects
	 *
	 * @var Sql_Group[]
	 */
	private $group = [];

	/**
	 * Array of joins
	 *
	 * @var Sql_Join[]
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
	 * Add Sql_Where to the query
	 *
	 * @param Sql_Where $where Where.
	 * @return void
	 */
	public function add_where( Sql_Where $where ) {
		$this->where[] = $where;
	}

	/**
	 * Reset all the Sql_Where objects
	 *
	 * @return void
	 */
	public function reset_where() {
		$this->where = [];
	}

	/**
	 * Get the Sql_Where objects
	 *
	 * @return list<Sql_Where>
	 */
	public function get_where() {
		return $this->where;
	}

	/**
	 * Add Sql_Select to query
	 *
	 * @param Sql_Select $select Select.
	 * @return void
	 */
	public function add_select( Sql_Select $select ) {
		$this->select[] = $select;
	}

	/**
	 * Add array of Sql_Select objects
	 *
	 * @param Sql_Select[] $selects Selects.
	 * @return void
	 */
	public function add_selects( array $selects ) {
		$this->select = array_merge( $this->select, $selects );
	}

	/**
	 * Add Sql_From to query.
	 *
	 * @param Sql_From $from From.
	 * @return void
	 */
	public function add_from( Sql_From $from ) {
		$this->from[] = $from;
	}

	/**
	 * Add Sql_Join to query.
	 *
	 * @param Sql_Join $join Join.
	 * @return void
	 */
	public function add_join( Sql_Join $join ) {
		$this->joins[] = $join;
	}

	/**
	 * Add Sql_Group to query
	 *
	 * @param Sql_Group $group Group.
	 * @return void
	 */
	public function add_group( Sql_Group $group ) {
		$this->group[] = $group;
	}

	/**
	 * Add the selects from another query
	 *
	 * @param Sql_Query $query Query.
	 * @return void
	 */
	public function add_select_only( Sql_Query $query ) {
		$this->select = array_merge( $this->select, $query->select );
	}

	/**
	 * Add everything from a query except the WHERE, and return the WHERE
	 *
	 * @param Sql_Query $query Query.
	 * @return Sql_Where[]
	 */
	public function add_query_except_where( Sql_Query $query ) {
		$this->select = array_merge( $this->select, $query->select );
		$this->from = array_merge( $this->from, $query->from );
		$this->group = array_merge( $this->group, $query->group );
		$this->joins = array_merge( $this->joins, $query->joins );
		return $query->where;
	}

	/**
	 * Add another query
	 *
	 * @param Sql_Query $query Query.
	 * @return void
	 */
	public function add_query( Sql_Query $query ) {
		$this->where = array_merge( $this->where, $query->where );
		$this->select = array_merge( $this->select, $query->select );
		$this->from = array_merge( $this->from, $query->from );
		$this->group = array_merge( $this->group, $query->group );
		$this->joins = array_merge( $this->joins, $query->joins );
	}

	/**
	 * Get the query as a SQL statement
	 *
	 * @param Sql_Modifier $modifier Modifier for the query.
	 * @return string
	 */
	public function get_as_sql( Sql_Modifier $modifier = null ) {
		global $wpdb;

		if ( $modifier === null ) {
			$modifier = new Sql_Modifier();
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
