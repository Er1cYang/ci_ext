<?php
namespace ci_ext\web;
use ci_ext\db\Table;

use ci_ext\web\helpers\Html;

class Sort extends \ci_ext\core\Object {
	const SORT_ASC = false;
	const SORT_DESC = true;
	public $multiSort = false;
	public $modelClass;
	public $attributes = array ();
	public $sortVar = 'sort';
	public $descTag = 'desc';
	public $defaultOrder;
	public $route = '';
	public $separators = array ('-', '.' );
	public $params;
	
	private $_directions;
	
	public function __construct($modelClass = null) {
		$this->modelClass = $modelClass;
	}
	
	public function applyOrder($criteria) {
		$order = $this->getOrderBy ( $criteria );
		if (! empty ( $order )) {
			if (! empty ( $criteria->order ))
				$criteria->order .= ', ';
			$criteria->order .= $order;
		}
	}
	
	public function getOrderBy($criteria = null) {
		$directions = $this->getDirections ();
		if (empty ( $directions ))
			return is_string ( $this->defaultOrder ) ? $this->defaultOrder : '';
		else {
			$orders = array ();
			foreach ( $directions as $attribute => $descending ) {
				$definition = $this->resolveAttribute ( $attribute );
				if (is_array ( $definition )) {
					if ($descending)
						$orders [] = isset ( $definition ['desc'] ) ? $definition ['desc'] : $attribute . ' DESC';
					else
						$orders [] = isset ( $definition ['asc'] ) ? $definition ['asc'] : $attribute;
				} else if ($definition !== false) {
					$attribute = $definition;
					if (($pos = strpos ( $attribute, '.' )) !== false)
						$attribute = '`'.substr ( $attribute, 0, $pos ).'`.`' . substr ( $attribute, $pos + 1 ).'`';
					else
						$attribute = ($criteria === null || $criteria->alias === null ? Table::model ( $this->modelClass )->getTableAlias ( true ) : $criteria->alias) . '.`'.$attribute.'`';
					$orders [] = $descending ? $attribute . ' DESC' : $attribute;
				}
			}
			return implode ( ', ', $orders );
		}
	}
	
	public function link($attribute, $label = null, $htmlOptions = array()) {
		if ($label === null)
			$label = $this->resolveLabel ( $attribute );
		if (($definition = $this->resolveAttribute ( $attribute )) === false)
			return $label;
		$directions = $this->getDirections ();
		if (isset ( $directions [$attribute] )) {
			$class = $directions [$attribute] ? 'desc' : 'asc';
			if (isset ( $htmlOptions ['class'] ))
				$htmlOptions ['class'] .= ' ' . $class;
			else
				$htmlOptions ['class'] = $class;
			$descending = ! $directions [$attribute];
			unset ( $directions [$attribute] );
		} else if (is_array ( $definition ) && isset ( $definition ['default'] ))
			$descending = $definition ['default'] === 'desc';
		else
			$descending = false;
		
		if ($this->multiSort)
			$directions = array_merge ( array ($attribute => $descending ), $directions );
		else
			$directions = array ($attribute => $descending );
		
		$url = $this->createUrl ( get_instance(), $directions );
		
		return $this->createLink ( $attribute, $label, $url, $htmlOptions );
	}
	
	public function resolveLabel($attribute) {
		$definition = $this->resolveAttribute ( $attribute );
		if (is_array ( $definition )) {
			if (isset ( $definition ['label'] ))
				return $definition ['label'];
		} else if (is_string ( $definition ))
			$attribute = $definition;
		if ($this->modelClass !== null)
			return Table::model ( $this->modelClass )->getAttributeLabel ( $attribute );
		else
			return $attribute;
	}
	
	public function getDirections() {
		if ($this->_directions === null) {
			$this->_directions = array ();
			if (isset ( $_GET [$this->sortVar] ) && is_string ( $_GET [$this->sortVar] )) {
				$attributes = explode ( $this->separators [0], $_GET [$this->sortVar] );
				foreach ( $attributes as $attribute ) {
					if (($pos = strrpos ( $attribute, $this->separators [1] )) !== false) {
						$descending = substr ( $attribute, $pos + 1 ) === $this->descTag;
						if ($descending)
							$attribute = substr ( $attribute, 0, $pos );
					} else
						$descending = false;
					
					if (($this->resolveAttribute ( $attribute )) !== false) {
						$this->_directions [$attribute] = $descending;
						if (! $this->multiSort)
							return $this->_directions;
					}
				}
			}
			if ($this->_directions === array () && is_array ( $this->defaultOrder ))
				$this->_directions = $this->defaultOrder;
		}
		return $this->_directions;
	}

	public function getDirection($attribute) {
		$this->getDirections ();
		return isset ( $this->_directions [$attribute] ) ? $this->_directions [$attribute] : null;
	}
	
	public function createUrl($controller, $directions) {
		$sorts = array ();
		foreach ( $directions as $attribute => $descending )
			$sorts [] = $descending ? $attribute . $this->separators [1] . $this->descTag : $attribute;
		$params = $this->params === null ? $_GET : $this->params;
		$params [$this->sortVar] = implode ( $this->separators [0], $sorts );
		return $controller->createUrl ( $this->route, $params );
	}
	
	public function resolveAttribute($attribute) {
		if ($this->attributes !== array ())
			$attributes = $this->attributes;
		else if ($this->modelClass !== null)
			$attributes = Table::model ( $this->modelClass )->attributeNames ();
		else
			return false;
		foreach ( $attributes as $name => $definition ) {
			if (is_string ( $name )) {
				if ($name === $attribute)
					return $definition;
			} else if ($definition === '*') {
				if ($this->modelClass !== null && Table::model ( $this->modelClass )->hasAttribute ( $attribute ))
					return $attribute;
			} else if ($definition === $attribute)
				return $attribute;
		}
		return false;
	}
	
	protected function createLink($attribute, $label, $url, $htmlOptions) {
		return Html::link ( $label, $url, $htmlOptions );
	}
}