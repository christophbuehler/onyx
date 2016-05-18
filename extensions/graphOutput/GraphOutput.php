<?php

class GraphOutput {
	function __construct($view) {
		$this->db = $view->model->db;
        
        // this id increments for each new table instance
        // and acts as an identifier
        $this->graphId = 1;
	}
	
	function show($args) {
		ob_start(); // make sure there is no output
		
		// required
		$this->type = $args['type'];
		$this->headers = explode(',', str_replace(', ', ',', $args['headers']));
		$this->values = is_array($args['values']) ? $args['values'] : explode(',', str_replace(', ', ',', $args['values']));
		
		$this->width = isset($args['width']) ? $args['width'] : 480;
		$this->height = isset($args['height']) ? $args['height'] : 360;
		
		if (isset($args['id']))
			$this->id = $args['id'];
		
		$this->colors = isset($args['colors']) ? explode(',', $args['colors']) : $this->get_colors();
		
		return $this->show_graph();
	}
	
	function get_colors() {
		$colors = array();
		foreach ($this->headers as $header) {
			srand(base_convert($header, 16, 10));
			array_push($colors, 'rgb(' . (int) rand(0, 255) . ', ' . (int) rand(0, 255) . ', ' . (int) rand(0, 255) . ')');
		}
		return $colors;
	}
	
	function show_graph() {
		return '<div ' . (isset($this->id) ? 'id="' . $this->id . '" ' : '') . 'data-height="' . $this->height . '" data-width="' . $this->width . '" data-colors="' . implode(';', $this->colors) . '" data-headers="' . implode(';', $this->headers) . '" data-values="' . implode(';', $this->values) . '" data-type="' . $this->type . '" class="graph-output"></div>';
	}
}
	
	