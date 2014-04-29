<?php

/**
 * Notification
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @package Hermes
 * @update 20.05.13
*/

class Notification {
	// ATTRIBUTES
	private $id 		= 0;
	private $rPlayer 	= 0;
	private $title 		= '';
	private $content 	= '';
	private $dSending;
	private $readed 	= 0;
	private $archived 	= 0;
	
	// CONSTRUCTOR
	public function __construct() {
		$this->dSending = CTC::now();
	}

	// GETTERS AND SETTERS
	public function getId() 					{ return $this->id; }
	public function getRPlayer() 				{ return $this->rPlayer; }
	public function getTitle()					{ return $this->title; }
	public function getContent() 				{ return $this->content; }
	public function getDSending() 				{ return $this->dSending; }
	public function getReaded() 				{ return $this->readed; }
	public function getArchived()				{ return $this->archived; }

	public function setId($v) {
		$this->id = $v;
	}

	public function setRPlayer($v) {
		$this->rPlayer = $v;
	}

	public function setTitle($v) {
		if (strlen($v) <= 100) {
			$this->title = $v; 
		} else {
			CTR::$alert->add('Le titre de la notification est trop long.', ALT_BUG_ERROR);
		}
	}

	public function setContent($v) {
		$this->content = $v; 
	}

	public function addContent($t) {
		$this->content .= $t;
		return $this;
	}

	public function addBeg() {
		return $this->addContent('<p>');
	}
	public function addTxt($t) {
		return $this->addContent($t);
	}
	public function addStg($t) {
		return $this->addContent('<strong>' . $t . '</strong>');
	}
	public function addLnk($path, $title) {
		return $this->addContent('<a href="' . APP_ROOT . $path . '">' . $title . '</a>');
	}
	public function addBrk() {
		return $this->addContent('</p><p>');
	}
	public function addSep() {
		return $this->addContent('</p><hr /><p>');
	}
	public function addEnd() {
		return $this->addContent('</p>');
	}

	public function setDSending($v) {
		if (isset($v)) {
			$this->dSending = $v; 
		} else {
			$this->dSending = Utils::now();
		}
	}

	public function setReaded($v) {
		if (isset($v) && ($v == 0 || $v == 1)) {
			$this->readed = $v; 
		} else {
			CTR::$alert->add('La notification peut être lue ou non-lue, il n\' a pas d\'autres possibilités !');
		}
	}

	public function setArchived($v)	{
		if (isset($v) && ($v == 0 || $v == 1)) {
			$this->archived = $v; 
		} else {
			CTR::$alert->add('La notification peut être archivée ou non-archivée, il n\' a pas d\'autres possibilités !');
		}
	}
}
?>