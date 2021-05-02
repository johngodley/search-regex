<?php

namespace SearchRegex\Sql;

class Sql_Query {
	private $where = [];
	private $select = [];
	private $from = [];
	private $group = [];
	private $joins = [];
	private $offset = null;
	private $limit = null;
	private $order = null;
	private $order_direction = 'ASC';

	public function set_order( $order, $order_direction = 'ASC' ) {
		$order_direction = strtoupper( $order_direction );
		$this->order = $order;

		if ( in_array( $order_direction, [ 'ASC', 'DESC' ], true ) ) {
			$this->order_direction = $order_direction;
		}
	}

	public function set_paging( $offset, $limit ) {
		$this->offset = intval( $offset, 10 );
		$this->limit = intval( $limit, 10 );
	}

	public function add_where( Sql_Where $where ) {
		$this->where[] = $where;
	}

	public function reset_where() {
		$this->where = [];
	}

	public function get_where() {
		return $this->where;
	}

	public function add_select( Sql_Select $select ) {
		$this->select[] = $select;
	}

	public function add_selects( array $selects ) {
		$this->select = array_merge( $this->select, $selects );
	}

	public function add_from( Sql_From $from ) {
		$this->from[] = $from;
	}

	public function add_join( Sql_Join $join ) {
		$this->joins[] = $join;
	}

	public function add_group( Sql_Group $group ) {
		$this->group[] = $group;
	}

	public function add_select_only( Sql_Query $query ) {
		$this->select = array_merge( $this->select, $query->select );
	}

	public function add_query_except_where( Sql_Query $query ) {
		$this->select = array_merge( $this->select, $query->select );
		$this->from = array_merge( $this->from, $query->from );
		$this->group = array_merge( $this->group, $query->group );
		$this->joins = array_merge( $this->joins, $query->joins );
		return $query->where;
	}

	public function add_query( Sql_Query $query ) {
		$this->where = array_merge( $this->where, $query->where );
		$this->select = array_merge( $this->select, $query->select );
		$this->from = array_merge( $this->from, $query->from );
		$this->group = array_merge( $this->group, $query->group );
		$this->joins = array_merge( $this->joins, $query->joins );
	}

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

	private function get_query_section( $label, array $values, $separator = ' ' ) {
		if ( count( $values ) > 0 ) {
			return [ $label . ' ' . implode( $separator, $values ) ];
		}

		return [];
	}
}
