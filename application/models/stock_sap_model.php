<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class stock_sap_model extends CI_Model {

	var $bd_logistica     = 'bd_logistica..';
	var $bd_planificacion = 'bd_planificacion..';

	public function __construct()
	{
		parent::__construct();
	}


	public function get_stock($tipo_op = '', $mostrar = array(), $filtrar = array())
	{
		if ($tipo_op == 'MOVIL')
		{
			return $this->_get_stock_movil($mostrar, $filtrar);
		}
		else
		{
			return $this->_get_stock_fijo($mostrar, $filtrar);
		}
	}




	public function _get_stock_movil($mostrar = array(), $filtrar = array())
	{
		$arr_result = array();

		// fecha stock
		if (in_array('fecha', $mostrar))
		{
			$this->db->select('convert(varchar(20), s.fecha_stock, 102) as fecha_stock', FALSE);
			$this->db->group_by('convert(varchar(20), s.fecha_stock, 102)');
			$this->db->order_by('fecha_stock');
		}

		// tipo de almacen
		if ($filtrar['sel_tiposalm'] == 'sel_tiposalm')
		{
			if (in_array('tipo_alm', $mostrar))
			{
				$this->db->select('t.tipo as tipo_almacen');
				$this->db->group_by('t.tipo');
				$this->db->order_by('t.tipo');
			}

			// almacenes
			if (in_array('almacen', $mostrar))
			{
				$this->db->select('ta.centro, ta.cod_almacen, a.des_almacen');
				$this->db->group_by('ta.centro, ta.cod_almacen, a.des_almacen');
				$this->db->order_by('ta.centro, ta.cod_almacen, a.des_almacen');
			}
		}
		else
		{
			$this->db->select('a.centro, s.cod_bodega, a.des_almacen');
			$this->db->group_by('a.centro, s.cod_bodega, a.des_almacen');
			$this->db->order_by('a.centro, s.cod_bodega, a.des_almacen');
		}

		// tipos de articulos
		if (in_array('tipo_articulo', $mostrar))
		{
			//$this->db->select("CASE WHEN (s.largo_serie=15) THEN 'EQUIPOS' WHEN (s.largo_serie=19) THEN 'SIMCARD' ELSE 'OTROS' END as tipo_articulo", FALSE);
			//$this->db->group_by("CASE WHEN (s.largo_serie=15) THEN 'EQUIPOS' WHEN (s.largo_serie=19) THEN 'SIMCARD' ELSE 'OTROS' END", FALSE);
			//$this->db->order_by('tipo_articulo');
			$this->db->select("CASE WHEN (substring(cod_articulo,1,8)='PKGCLOTK' OR substring(cod_articulo,1,2)='TS') THEN 'SIMCARD' WHEN substring(cod_articulo, 1,2) in ('TM','TO','TC','PK','PO') THEN 'EQUIPOS' ELSE 'OTROS' END as tipo_articulo", FALSE);
			$this->db->group_by("CASE WHEN (substring(cod_articulo,1,8)='PKGCLOTK' OR substring(cod_articulo,1,2)='TS') THEN 'SIMCARD' WHEN substring(cod_articulo, 1,2) in ('TM','TO','TC','PK','PO') THEN 'EQUIPOS' ELSE 'OTROS' END", FALSE);
			$this->db->order_by('tipo_articulo');
		}

		// materiales
		if (in_array('material', $mostrar))
		{
			$this->db->select('s.cod_articulo');
			$this->db->group_by('s.cod_articulo');
			$this->db->order_by('s.cod_articulo');
		}

		// lotes
		if (in_array('lote', $mostrar))
		{
			$this->db->select('s.lote');
			$this->db->group_by('s.lote');
		}

		// cantidades y tipos de stock
		if (in_array('tipo_stock', $mostrar))
		{
			$this->db->select('sum(s.libre_utilizacion) as LU', FALSE);
			$this->db->select('sum(s.bloqueado)         as BQ', FALSE);
			$this->db->select('sum(s.contro_calidad)    as CC', FALSE);
			$this->db->select('sum(s.transito_traslado) as TT', FALSE);
			$this->db->select('sum(s.otros)             as OT', FALSE);

			$this->db->select('sum(s.libre_utilizacion*p.pmp) as VAL_LU', FALSE);
			$this->db->select('sum(s.bloqueado*p.pmp)         as VAL_BQ', FALSE);
			$this->db->select('sum(s.contro_calidad*p.pmp)    as VAL_CC', FALSE);
			$this->db->select('sum(s.transito_traslado*p.pmp) as VAL_TT', FALSE);
			$this->db->select('sum(s.otros*p.pmp)             as VAL_OT', FALSE);
		}
		$this->db->select('sum(s.libre_utilizacion + s.bloqueado + s.contro_calidad + s.transito_traslado + s.otros) as total', FALSE);
		$this->db->select('sum((s.libre_utilizacion + s.bloqueado + s.contro_calidad + s.transito_traslado + s.otros)*p.pmp) as monto', FALSE);

		// tablas
		if ($filtrar['sel_tiposalm'] == 'sel_tiposalm')
		{
			$this->db->from($this->bd_logistica . 'stock_scl s');
			$this->db->join($this->bd_planificacion . 'ca_stock_sap_04 p',"s.centro=p.centro and s.cod_articulo=p.material and s.lote=p.lote and p.estado_stock='01'",'left');
			$this->db->join($this->bd_logistica . 'cp_tipos_almacenes ta', 's.centro=ta.centro and s.cod_bodega=ta.cod_almacen');
			$this->db->join($this->bd_logistica . 'cp_tiposalm t',         't.id_tipo=ta.id_tipo');
			$this->db->join($this->bd_logistica . 'cp_almacenes a',        's.centro=a.centro and s.cod_bodega=a.cod_almacen', 'left');
		}
		else
		{
			$this->db->from($this->bd_logistica . 'stock_scl s');
			$this->db->join($this->bd_planificacion . 'ca_stock_sap_04 p',"s.centro=p.centro and s.cod_articulo=p.material and s.lote=p.lote and p.estado_stock='01'",'left');
			$this->db->join($this->bd_logistica . 'cp_almacenes a', 's.centro=a.centro and s.cod_bodega=a.cod_almacen', 'left');
		}


		// condiciones
		// fechas
		if (array_key_exists('fecha', $filtrar))
		{
			$this->db->where_in('s.fecha_stock', $filtrar['fecha']);
		}

		// tipos de almacen
		if ($filtrar['sel_tiposalm'] == 'sel_tiposalm')
		{
			if (array_key_exists('tipo_alm', $filtrar))
			{
				$this->db->where_in('t.id_tipo', $filtrar['tipo_alm']);
			}
		}
		else if($filtrar['sel_tiposalm'] == 'sel_almacenes')
		{
			$this->db->where_in("s.centro+'-'+s.cod_bodega", $filtrar['almacenes']);
		}

		// tipos de articulo
		/*
		if (array_key_exists('tipo_articulo', $filtrar))
		{
			$this->db->where_in('tipo_articulo', $filtrar['tipo_articulo']);
		}
		*/

		$this->db->where('s.origen', 'SAP');

		if (in_array('tipo_stock', $mostrar))
		{
			$arr_filtro_tipo_stock = array();

			if (in_array('tipo_stock_equipos', $filtrar))
			{
				array_push($arr_filtro_tipo_stock, 'EQUIPOS');
			}

			if (in_array('tipo_stock_simcard', $filtrar))
			{
				array_push($arr_filtro_tipo_stock, 'SIMCARD');
			}

			if (in_array('tipo_stock_otros', $filtrar))
			{
				array_push($arr_filtro_tipo_stock, 'OTROS');
			}

			$this->db->where_in("CASE WHEN (substring(cod_articulo,1,8)='PKGCLOTK' OR substring(cod_articulo,1,2)='TS') THEN 'SIMCARD' WHEN substring(cod_articulo, 1,2) in ('TM','TO','TC','PK','PO') THEN 'EQUIPOS' ELSE 'OTROS' END",$arr_filtro_tipo_stock);
		}

		$arr_result = $this->db->get()->result_array();


		$arr_stock_tmp01 = array();
		// si tenemos tipos de articulo y no tenemos el detalle de estados de stock,
		// entonces hacemos columnas con los totales de tipos_articulo (equipos, simcards y otros)
		if (in_array('tipo_articulo', $mostrar) and !(in_array('tipo_stock', $mostrar)))
		{
			foreach($arr_result as $reg)
			{
				$arr_reg = array();
				$llave_total = '';
				$valor_total = 0;
				$valor_monto = 0;
				foreach ($reg as $campo_key => $campo_val)
				{
					if ($campo_key == 'tipo_articulo')
					{
						$llave_total = $campo_val;
					}
					else if ($campo_key == 'total')
					{
						$valor_total = $campo_val;
					}
					else if ($campo_key == 'monto')
					{
						$valor_monto = $campo_val;
					}
					else
					{
						$arr_reg[$campo_key] = $campo_val;
					}
				}
				$arr_reg[$llave_total] = $valor_total;
				$arr_reg['VAL_' . $llave_total] = $valor_monto;
				array_push($arr_stock_tmp01, $arr_reg);
			}


			$arr_stock = array();
			$llave_ant = '';
			$i = 0;
			foreach($arr_stock_tmp01 as $reg)
			{
				$i += 1;
				$llave_act = '';
				$llave_total = '';
				$llave_monto = '';
				$valor_total = 0;
				$valor_monto = 0;

				// determina la llave actual y el valor del campo total
				$arr_tmp = array();
				foreach ($reg as $campo_key => $campo_val)
				{
					if ($campo_key == 'EQUIPOS' || $campo_key == 'OTROS' || $campo_key == 'SIMCARD')
					{
						$llave_total = $campo_key;
						$valor_total = $campo_val;
					}
					else if ($campo_key == 'VAL_EQUIPOS' || $campo_key == 'VAL_OTROS' || $campo_key == 'VAL_SIMCARD')
					{
						$llave_monto = $campo_key;
						$valor_monto = $campo_val;
					}
					else
					{
						$llave_act .= $campo_val;
						$arr_tmp[$campo_key] = $campo_val;
					}
				}

				// si la llave es distinta y no es el primer registro, agregamos el arreglo anterior
				// y guardamos los nuevos valores en el arreglo anterior
				if ($llave_act != $llave_ant)
				{
					if ($i != 1)
					{
						//echo "i: $i llave_act: $llave_act llave_ant: $llave_ant<br>";
						array_push($arr_stock, $arr_ant);
					}

					$arr_ant = array();
					$arr_ant = $arr_tmp;
					$arr_ant['EQUIPOS']     = 0;
					$arr_ant['VAL_EQUIPOS'] = 0;
					$arr_ant['SIMCARD']     = 0;
					$arr_ant['VAL_SIMCARD'] = 0;
					$arr_ant['OTROS']       = 0;
					$arr_ant['VAL_OTROS']   = 0;
					$arr_ant[$llave_total] = $valor_total;
					$arr_ant[$llave_monto] = $valor_monto;

					$llave_ant = $llave_act;
				}
				// si la llave es la misma, solo agregamos el valor de equipos, otros o simcard
				else
				{
					$arr_ant[$llave_total] = $valor_total;
					$arr_ant[$llave_monto] = $valor_monto;
				}
			}

			// finalmente agregamos el ultimo valor del arreglo anterior
			if ($i > 0)
			{
				array_push($arr_stock, $arr_ant);
			}

			$arr_result = $arr_stock;
		}

		//var_dump($arr_result);

		return $arr_result;
	}


	public function _get_stock_fijo($mostrar = array(), $filtrar = array())
	{
		$arr_result = array();

		// fecha stock
		if (in_array('fecha', $mostrar))
		{
			$this->db->select('convert(varchar(20), s.fecha_stock, 102) as fecha_stock', FALSE);
			$this->db->group_by('convert(varchar(20), s.fecha_stock, 102)');
			$this->db->order_by('fecha_stock');
		}

		// tipo de almacen
		if ($filtrar['sel_tiposalm'] == 'sel_tiposalm')
		{
			if (in_array('tipo_alm', $mostrar))
			{
				$this->db->select('t.tipo as tipo_almacen');
				$this->db->group_by('t.tipo');
				$this->db->order_by('t.tipo');
			}

			if (in_array('almacen', $mostrar))
			{
				$this->db->select('ta.centro, ta.cod_almacen, a.des_almacen');
				$this->db->group_by('ta.centro, ta.cod_almacen, a.des_almacen');
				$this->db->order_by('ta.centro, ta.cod_almacen, a.des_almacen');
			}

		}
		else
		{
			$this->db->select('s.centro, s.almacen, a.des_almacen');
			$this->db->group_by('s.centro, s.almacen, a.des_almacen');
			$this->db->order_by('s.centro, s.almacen, a.des_almacen');
		}

		// almacenes
		// tipos de articulos
		if (in_array('tipo_articulo', $mostrar))
		{
			//$this->db->select("CASE WHEN (substring(cod_articulo,1,8)='PKGCLOTK' OR substring(cod_articulo,1,2)='TS') THEN 'SIMCARD' WHEN substring(cod_articulo, 1,2) in ('TM','TO','TC','PK','PO') THEN 'EQUIPOS' ELSE 'OTROS' END as tipo_articulo", FALSE);
			//$this->db->group_by("CASE WHEN (substring(cod_articulo,1,8)='PKGCLOTK' OR substring(cod_articulo,1,2)='TS') THEN 'SIMCARD' WHEN substring(cod_articulo, 1,2) in ('TM','TO','TC','PK','PO') THEN 'EQUIPOS' ELSE 'OTROS' END", FALSE);
			//$this->db->order_by('tipo_articulo');
		}

		// materiales
		if (in_array('material', $mostrar))
		{
			$this->db->select('s.material, m.desc_material, s.umb');
			$this->db->join($this->bd_logistica . 'cp_material_fija m', 's.material=m.material', 'left');
			$this->db->group_by('s.material, m.desc_material, s.umb');
			$this->db->order_by('s.material, m.desc_material, s.umb');
		}

		// lotes
		if (in_array('lote', $mostrar))
		{
			$this->db->select('s.lote');
			$this->db->group_by('s.lote');
		}

		// cantidades y tipos de stock
		// cantidades y tipos de stock
		if (in_array('tipo_stock', $mostrar))
		{
			$this->db->select('s.estado');
			$this->db->group_by('s.estado');
			$this->db->order_by('s.estado');
		}
		$this->db->select('sum(s.cantidad) as cantidad, sum(s.valor) as monto', FALSE);

		// tablas
		if ($filtrar['sel_tiposalm'] == 'sel_tiposalm')
		{
			$this->db->from($this->bd_logistica . 'bd_stock_sap_fija s');
			$this->db->join($this->bd_logistica . 'cp_tipos_almacenes ta', 's.almacen=ta.cod_almacen and s.centro=ta.centro');
			$this->db->join($this->bd_logistica . 'cp_tiposalm t',         't.id_tipo=ta.id_tipo');
			$this->db->join($this->bd_logistica . 'cp_almacenes a',        'a.centro=ta.centro and a.cod_almacen=ta.cod_almacen', 'left');
		}
		else
		{
			$this->db->from($this->bd_logistica . 'bd_stock_sap_fija s');
			$this->db->join($this->bd_logistica . 'cp_almacenes a', 'a.centro=s.centro and a.cod_almacen=s.almacen', 'left');
		}

		// condiciones
		// fechas
		if (array_key_exists('fecha', $filtrar))
		{
			$this->db->where_in('s.fecha_stock', $filtrar['fecha']);
		}

		// tipos de almacen
		if ($filtrar['sel_tiposalm'] == 'sel_tiposalm')
		{
			if (array_key_exists('tipo_alm', $filtrar))
			{
				$this->db->where_in('t.id_tipo', $filtrar['tipo_alm']);
			}
		}
		else
		{
			$this->db->where_in("s.centro+'-'+s.almacen", $filtrar['almacenes']);
		}

		// tipos de articulo
		/*
		if (array_key_exists('tipo_articulo', $filtrar))
		{
			$this->db->where_in('tipo_articulo', $filtrar['tipo_articulo']);
		}
		*/

		$arr_result = $this->db->get()->result_array();
		//print_r($arr_result);


		return $arr_result;
	}



	public function get_combo_fechas($tipo_op = '')
	{
		$arr_fecha_tmp = ($tipo_op == 'MOVIL') ? $this->_get_combo_fechas_movil() : $this->_get_combo_fechas_fija();
		$arr_fecha = array('ultimodia' => array(), 'todas' => array());

		$año_mes_ant = '';
		foreach($arr_fecha_tmp as $key => $val)
		{
			if ($año_mes_ant != substr($key, 0, 6))
			{
				$arr_fecha['ultimodia'][$key] = $val;
			}

			$año_mes_ant = substr($key, 0, 6);
		}

		$arr_fecha['todas'] = $arr_fecha_tmp;

		return $arr_fecha;
	}

	private function _get_combo_fechas_movil()
	{
		$arr_result = array();
		$arr_combo  = array();

		$this->db->select('convert(varchar(20), fecha_stock, 112) as fecha_stock, convert(varchar(20), fecha_stock, 103) as fecha');
		$this->db->order_by('fecha_stock','desc');
		$arr_result = $this->db->get($this->bd_logistica . 'stock_scl_fechas')->result_array();

		foreach($arr_result as $reg)
		{
			$arr_combo[$reg['fecha_stock']] = $reg['fecha'];
		}

		return $arr_combo;
	}

	private function _get_combo_fechas_fija()
	{
		$arr_result = array();
		$arr_combo  = array();

		$this->db->select('convert(varchar(20), fecha_stock, 112) as fecha_stock, convert(varchar(20), fecha_stock, 103) as fecha');
		$this->db->order_by('fecha_stock','desc');
		$arr_result = $this->db->get($this->bd_logistica . 'bd_stock_sap_fija_fechas')->result_array();

		foreach($arr_result as $reg)
		{
			$arr_combo[$reg['fecha_stock']] = $reg['fecha'];
		}

		return $arr_combo;
	}

	public function get_detalle_series($centro = '', $almacen = '', $material = '', $lote = '')
	{
		if ($almacen != '' && $almacen != '_')
		{
			if ($centro   != '' && $centro   != '_') $this->db->where('s.centro', $centro);
			if ($almacen  != '' && $almacen  != '_') $this->db->where('s.almacen', $almacen);
			if ($material != '' && $material != '_') $this->db->where('s.material', $material);
			if ($lote     != '' && $lote     != '_') $this->db->where('s.lote', $lote);

			$this->db->select('convert(varchar(20), s.fecha_stock, 103) as fecha_stock, s.centro, s.almacen, a.des_almacen, s.material, s.lote, s.serie, s.estado_stock, convert(varchar(20), s.modificado_el, 103) as fecha_modificacion, s.modificado_por, u.nom_usuario');

			$this->db->from($this->bd_logistica . 'bd_stock_sap as s');
			$this->db->join($this->bd_logistica . 'cp_almacenes as a', 'a.centro=s.centro and a.cod_almacen=s.almacen', 'left');
			$this->db->join($this->bd_logistica . 'cp_usuarios as u', 'u.usuario=s.modificado_por', 'left');
			$this->db->order_by('material, lote, serie');

			return $this->db->get()->result_array();
		}
		else
		{
			return array();
		}
	}



}

/* End of file stock_sap_model.php */
/* Location: ./application/models/stock_sap_model.php */