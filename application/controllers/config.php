<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Config extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		//$this->output->enable_profiler(TRUE);
	}


	public function index() {
		$this->usuarios();
	}


	public function usuarios($pag = 0)
	{

		$this->load->model('usuarios_model');

		$this->load->library('pagination');
		$limite_por_pagina = 15;
		$config_pagination = array(
									'total_rows'  => $this->usuarios_model->total_usuarios(),
									'per_page'    => $limite_por_pagina,
									'base_url'    => site_url('config/usuarios'),
									'uri_segment' => 3,
									'num_links'   => 5,
									'first_link'  => 'Primero',
									'last_link'   => 'Ultimo',
									'next_link'   => '<img src="'. base_url() . 'img/ic_right.png" />',
									'prev_link'   => '<img src="'. base_url() . 'img/ic_left.png" />',
								);
		$this->pagination->initialize($config_pagination);

		$datos_hoja = $this->usuarios_model->get_usuarios($limite_por_pagina, $pag);

		if ($this->input->post('formulario')=='usuarios')
		{
			foreach($datos_hoja as $reg)
			{
				$this->form_validation->set_rules($reg['id'].'-tipo', 'Tipo', 'trim|required');
				$this->form_validation->set_rules($reg['id'].'-nombre', 'Nombre', 'trim|required');
				$this->form_validation->set_rules($reg['id'].'-activo', 'Activo', '');
			}
		}
		else if ($this->input->post('formulario')=='agregar')
		{
			$this->form_validation->set_rules('agr_tipo', 'Tipo', 'trim|required');
			$this->form_validation->set_rules('agr_nombre', 'Nombre', 'trim|required');
			$this->form_validation->set_rules('agr_activo', 'Activo', '');
		}
		else if ($this->input->post('formulario')=='borrar')
		{
			$this->form_validation->set_rules('id_borrar', '', 'trim|required');
		}

		$this->form_validation->set_error_delimiters('<div class="error round">', '</div>');
		$this->form_validation->set_message('required', 'Ingrese un valor para %s');

		if ($this->form_validation->run() == FALSE)
		{
			$data = array(
					'datos_hoja'      => $datos_hoja,
					'msg_alerta'      => $this->session->flashdata('msg_alerta'),
					'links_paginas'   => $this->pagination->create_links(),
					'combo_tipos'     => array(
												'' => 'Seleccione un tipo...', 
												'AUD' => 'Auditor', 
												'DIG'=>'Digitador'
												),
				);
			$this->_render_view('usuarios', $data);
		}
		else
		{
			if ($this->input->post('formulario') == 'usuarios')
			{
				$cant_modif = 0;

				foreach($datos_hoja as $reg)
				{
					if ((set_value($reg['id'].'-tipo') != $reg['tipo']) or 
						(set_value($reg['id'].'-nombre') != $reg['nombre']) or
						(set_value($reg['id'].'-activo') != $reg['activo']))
					{
						$this->usuarios_model->guardar($reg['id'], set_value($reg['id'].'-tipo'), set_value($reg['id'].'-nombre'), set_value($reg['id'].'-activo'));
						$cant_modif += 1;
					}
				}
				$this->session->set_flashdata('msg_alerta', (($cant_modif > 0) ? $cant_modif . ' usuario(s) modificados correctamente' : ''));
			}
			else if ($this->input->post('formulario') == 'agregar')
			{
				$this->usuarios_model->guardar(0, set_value('agr_tipo'), set_value('agr_nombre'), set_value('agr_activo'));
				$this->session->set_flashdata('msg_alerta', 'Usuario (' . set_value('agr_nombre') . ') agregado correctamente');
			}
			else if ($this->input->post('formulario') == 'borrar')
			{
				if ($this->usuarios_model->get_cant_registros_usuario(set_value('id_borrar')) > 0)
				{
					$this->session->set_flashdata('msg_alerta', 'Usuario (id=' . set_value('id_borrar') . ') está ocupado, por lo que no se puede borrar');
				}
				else
				{
					$this->usuarios_model->borrar(set_value('id_borrar'));
					$this->session->set_flashdata('msg_alerta', 'Inventario (id=' . set_value('id_borrar') . ') borrado correctamente');
				}
			}
			
			redirect('config/usuarios/' . $pag);
		}

	}



	public function materiales($filtro = '', $pag = 0)
	{
		$this->load->model('catalogo_model');

		$filtro = ($filtro == '') ? '_' : urldecode($filtro);

		$this->load->library('pagination');
		$limite_por_pagina = 15;
		$config_pagination = array(
									'total_rows'  => $this->catalogo_model->total_materiales($filtro),
									'per_page'    => $limite_por_pagina,
									'base_url'    => site_url('config/materiales/' . $filtro),
									'uri_segment' => 4,
									'num_links'   => 5,
									'first_link'  => 'Primero',
									'last_link'   => 'Ultimo',
									'next_link'   => '<img src="'. base_url() . 'img/ic_right.png" />',
									'prev_link'   => '<img src="'. base_url() . 'img/ic_left.png" />',
								);
		$this->pagination->initialize($config_pagination);

		$datos_hoja = $this->catalogo_model->get_materiales($filtro, $limite_por_pagina, $pag);

		if ($this->input->post('formulario')=='materiales')
		{
			foreach($datos_hoja as $reg)
			{
				$this->form_validation->set_rules($reg['catalogo'].'-catalogo', 'Catalogo', 'trim|required');
				$this->form_validation->set_rules($reg['catalogo'].'-descripcion', 'Descripcion', 'trim|required');
				$this->form_validation->set_rules($reg['catalogo'].'-pmp', 'PMP', 'trim|required');
			}			
		}
		else if ($this->input->post('formulario')=='agregar')
		{
			$this->form_validation->set_rules('agr_catalogo', 'Catalogo', 'trim|required');
			$this->form_validation->set_rules('agr_descripcion', 'Descripcion', 'trim|required');
			$this->form_validation->set_rules('agr_pmp', 'PMP', 'trim|required|decimal');
		}
		else if ($this->input->post('formulario')=='borrar')
		{
			$this->form_validation->set_rules('id_borrar', '', 'trim|required');
		}

		$this->form_validation->set_error_delimiters('<div class="error round">', '</div>');
		$this->form_validation->set_message('required', 'Ingrese un valor para %s');
		$this->form_validation->set_message('decimal', 'Ingrese un valor con formato decimal (####.#)');

		if ($this->form_validation->run() == FALSE)
		{
			$data = array(
					'datos_hoja'      => $datos_hoja,
					'filtro'          => ($filtro == '_') ? '' : $filtro,
					'links_paginas'   => $this->pagination->create_links(),
					'msg_alerta'      => $this->session->flashdata('msg_alerta'),
				);

			$this->_render_view('materiales', $data);
		}
		else
		{
			if ($this->input->post('formulario') == 'materiales')
			{
				$cant_modif = 0;
				foreach($datos_hoja as $reg)
				{
					if ((set_value($reg['catalogo'].'-catalogo') != $reg['catalogo']) or 
						(set_value($reg['catalogo'].'-descripcion') != $reg['descripcion']) or
						(set_value($reg['catalogo'].'-pmp') != $reg['pmp']))
					{	
						$this->catalogo_model->guardar(
														$reg['catalogo'], 
														set_value($reg['catalogo'].'-catalogo'), 
														set_value($reg['catalogo'].'-descripcion'),
														set_value($reg['catalogo'].'-pmp')
														);
						$cant_modif += 1;
					}
				$this->session->set_flashdata('msg_alerta', (($cant_modif > 0) ? $cant_modif . ' material(es) modificados correctamente' : ''));
				}
			}
			else if ($this->input->post('formulario') == 'agregar')
			{
				$this->catalogo_model->guardar('', set_value('agr_catalogo'), set_value('agr_descripcion'), set_value('agr_pmp'));
				$this->session->set_flashdata('msg_alerta', 'Material (' . set_value('agr_descripcion') . ') agregado correctamente');
			}
			else if ($this->input->post('formulario') == 'borrar')
			{
				if ($this->catalogo_model->get_cant_registros_catalogo(set_value('id_borrar')) > 0)
				{
					$this->session->set_flashdata('msg_alerta', 'Catalogo (id=' . set_value('id_borrar') . ') está ocupado, por lo que no se puede borrar.');
				}
				else
				{
					$this->catalogo_model->borrar(set_value('id_borrar'));
					$this->session->set_flashdata('msg_alerta', 'Inventario (id=' . set_value('id_borrar') . ') borrado correctamente');
				}
			}
			redirect('config/materiales/' . $filtro . '/' . $pag);
		}


	}

	public function act_precio_materiales()
	{
		$this->load->model('catalogo_model');
		$cant_regs = $this->catalogo_model->actualiza_precios();
		$this->session->set_flashdata('msg_alerta',  'Actualizacion terminada (' . $cant_regs . ' precios actualizados correctamente).');
		redirect('config/materiales');
	}


	/**
	 * Administrador de los tipos de ubicacion
	 * @param  integer $pag Numero de la pagina a desplegar
	 * @return nada
	 */
	public function tipo_ubicacion($pag = 0)
	{
		$this->load->model('inventario_model');

		$this->load->library('pagination');
		$limite_por_pagina = 15;
		$config_pagination = array(
									'total_rows'  => $this->inventario_model->total_inventarios_activos(),
									'per_page'    => $limite_por_pagina,
									'base_url'    => site_url('config/tipo_ubicacion'),
									'uri_segment' => 3,
									'num_links'   => 5,
									'first_link'  => 'Primero',
									'last_link'   => 'Ultimo',
									'next_link'   => '<img src="'. base_url() . 'img/ic_right.png" />',
									'prev_link'   => '<img src="'. base_url() . 'img/ic_left.png" />',
								);
		$this->pagination->initialize($config_pagination);


		$datos_hoja = $this->inventario_model->get_tipos_ubicacion($limite_por_pagina, $pag);

		if ($this->input->post('formulario')=='editar')
		{
			foreach($datos_hoja as $reg)
			{
				$this->form_validation->set_rules($reg['id'].'-tipo_inventario', 'Tipo Inventario', 'trim|required');
				$this->form_validation->set_rules($reg['id'].'-tipo_ubicacion',  'Tipo Ubicacion', 'trim|required');
			}			
		}
		else if ($this->input->post('formulario')=='agregar')
		{
			$this->form_validation->set_rules('agr-tipo_inventario', 'Tipo de inventario', 'trim|required');
			$this->form_validation->set_rules('agr-tipo_ubicacion', 'Tipo de ubicacion', 'trim|required');
		}
		else if ($this->input->post('formulario')=='borrar')
		{
			$this->form_validation->set_rules('id_borrar', '', 'trim|required');
		}

		$this->form_validation->set_error_delimiters('<div class="error round">', '</div>');
		$this->form_validation->set_message('required', 'Ingrese un valor para %s');


		if ($this->form_validation->run() == FALSE)
		{
			$data = array(
					'datos_hoja'             => $datos_hoja,
					'combo_tipos_inventario' => $this->inventario_model->get_combo_tipos_inventario(),
					'msg_alerta'             => $this->session->flashdata('msg_alerta'),
					'links_paginas'          => $this->pagination->create_links(),
				);

			$this->_render_view('tipos_ubicacion', $data);
		}
		else
		{
			if ($this->input->post('formulario') == 'editar')
			{
				$cant_modif = 0;

				foreach($datos_hoja as $reg)
				{
					if ((set_value($reg['id'].'-tipo_inventario') != $reg['tipo_inventario']) or 
						(set_value($reg['id'].'-tipo_ubicacion')  != $reg['tipo_ubicacion']))
					{
						$this->inventario_model->guardar_tipo_ubicacion($reg['id'], set_value($reg['id'].'-tipo_inventario'), set_value($reg['id'].'-tipo_ubicacion'));
						$cant_modif += 1;
					}
				}
				$this->session->set_flashdata('msg_alerta', (($cant_modif > 0) ? $cant_modif . ' inventario(s) modificados correctamente' : ''));
			}
			else if ($this->input->post('formulario') == 'agregar')
			{
				$this->inventario_model->guardar_tipo_ubicacion(0, set_value('agr-tipo_inventario'), set_value('agr-tipo_ubicacion'));
				$this->session->set_flashdata('msg_alerta', 'Tipo de Ubicacion (' . set_value('agr-tipo_inventario') . ', ' . set_value('agr-tipo_ubicacion') . ') agregado correctamente');
			}
			else if ($this->input->post('formulario') == 'borrar')
			{
				if ($this->inventario_model->get_cant_registros_tipo_ubicacion(set_value('id_borrar')) > 0)
				{
					$this->session->set_flashdata('msg_alerta', 'Inventario (id=' . set_value('id_borrar') . ') no está vacio, por lo que no se puede borrar');
				}
				else
				{
					$this->inventario_model->borrar_tipo_ubicacion(set_value('id_borrar'));
					$this->session->set_flashdata('msg_alerta', 'Inventario (id=' . set_value('id_borrar') . ') borrado correctamente');
				}
			}
			redirect('config/tipo_ubicacion/' . $pag);
		}
	}


	/**
	 * Asociacion de ubicaciones con el tipo de ubicacion
	 * @param  integer $pag Numero de pagina a desplegar
	 * @return nada
	 */
	public function ubicacion_tipo_ubicacion($pag = 0)
	{
		$this->load->model('inventario_model');

		$this->load->library('pagination');
		$limite_por_pagina = 15;
		$config_pagination = array(
									'total_rows'  => $this->inventario_model->total_ubicacion_tipo_ubicacion(),
									'per_page'    => $limite_por_pagina,
									'base_url'    => site_url('config/ubicacion_tipo_ubicacion'),
									'uri_segment' => 3,
									'num_links'   => 5,
									'first_link'  => 'Primero',
									'last_link'   => 'Ultimo',
									'next_link'   => '<img src="'. base_url() . 'img/ic_right.png" />',
									'prev_link'   => '<img src="'. base_url() . 'img/ic_left.png" />',
								);
		$this->pagination->initialize($config_pagination);


		$datos_hoja = $this->inventario_model->get_ubicacion_tipo_ubicacion($limite_por_pagina, $pag);

		if ($this->input->post('formulario')=='editar')
		{
			foreach($datos_hoja as $reg)
			{
				$this->form_validation->set_rules($reg['id'].'-tipo_inventario', 'Tipo Inventario', 'trim|required');
				$this->form_validation->set_rules($reg['id'].'-tipo_ubicacion',  'Tipo Ubicacion', 'trim|required');
				$this->form_validation->set_rules($reg['id'].'-ubicacion',  'Ubicacion', 'trim|required');
			}			
		}
		else if ($this->input->post('formulario')=='agregar')
		{
			$this->form_validation->set_rules('agr-tipo_inventario', 'Tipo de inventario', 'trim|required');
			$this->form_validation->set_rules('agr-ubicacion[]', 'Ubicaciones', 'trim|required');
			$this->form_validation->set_rules('agr-tipo_ubicacion', 'Tipo de ubicacion', 'trim|required');
		}
		else if ($this->input->post('formulario')=='borrar')
		{
			$this->form_validation->set_rules('id_borrar', '', 'trim|required');
		}

		$this->form_validation->set_error_delimiters('<div class="error round">', '</div>');
		$this->form_validation->set_message('required', 'Ingrese un valor para %s');


		if ($this->form_validation->run() == FALSE)
		{
			$arr_combo_tipo_ubic = array();
			foreach($datos_hoja as $reg)
			{
				if (!array_key_exists($reg['tipo_inventario'], $arr_combo_tipo_ubic))
				{
					$arr_combo_tipo_ubic[$reg['tipo_inventario']] = array();
				}
			}
			foreach($arr_combo_tipo_ubic as $key => $val)
			{
				$arr_combo_tipo_ubic[$key] = $this->inventario_model->get_combo_tipos_ubicacion($key);
			}


			$data = array(
					'datos_hoja'             => $datos_hoja,
					'combo_tipos_inventario' => $this->inventario_model->get_combo_tipos_inventario(),
					'combo_tipos_ubicacion'  => $arr_combo_tipo_ubic,
					'msg_alerta'             => $this->session->flashdata('msg_alerta'),
					'links_paginas'          => $this->pagination->create_links(),
				);

			$this->_render_view('ubicacion_tipo_ubicacion', $data);
		}
		else
		{
			if ($this->input->post('formulario') == 'editar')
			{
				$cant_modif = 0;

				foreach($datos_hoja as $reg)
				{
					if ((set_value($reg['id'].'-tipo_inventario') != $reg['tipo_inventario']) or 
						(set_value($reg['id'].'-ubicacion')       != $reg['ubicacion']) or
						(set_value($reg['id'].'-tipo_ubicacion')  != $reg['id_tipo_ubicacion']))
					{
						$this->inventario_model->guardar_ubicacion_tipo_ubicacion($reg['id'], set_value($reg['id'].'-tipo_inventario'), set_value($reg['id'].'-ubicacion'), set_value($reg['id'].'-tipo_ubicacion'));
						$cant_modif += 1;
					}
				}
				$this->session->set_flashdata('msg_alerta', (($cant_modif > 0) ? $cant_modif . ' inventario(s) modificados correctamente' : ''));
			}
			else if ($this->input->post('formulario') == 'agregar')
			{
				$count = 0;
				foreach($this->input->post('agr-ubicacion') as $val)
				{
					$this->inventario_model->guardar_ubicacion_tipo_ubicacion(0, set_value('agr-tipo_inventario'), $val, set_value('agr-tipo_ubicacion'));
					$count += 1;
				}
				$this->session->set_flashdata('msg_alerta', 'Se agregaron ' . $count . ' ubicaciones correctamente');
			}
			else if ($this->input->post('formulario') == 'borrar')
			{
				$this->inventario_model->borrar_ubicacion_tipo_ubicacion(set_value('id_borrar'));
				$this->session->set_flashdata('msg_alerta', 'Registro (id=' . set_value('id_borrar') . ') borrado correctamente');
			}
			redirect('config/ubicacion_tipo_ubicacion/' . $pag);
		}
	}

	
	/**
	 * Devuelve string JSON con las ubicaciones libres
	 * @param  string $tipo_inventario Tipo de inventario a buscar
	 * @return JSON con las ubicaciones libres
	 */
	public function get_json_ubicaciones_libres($tipo_inventario = '')
	{
		$this->load->model('inventario_model');
		$arr_ubic = $this->inventario_model->get_ubicaciones_libres($tipo_inventario);
		echo (json_encode($arr_ubic));
	}

	/**
	 * Devuelve string JSON con los tipos de ubicaciones
	 * @param  string $tipo_inventario Tipo de inventario a buscar
	 * @return JSON con los tipos de ubicacion
	 */
	public function get_json_tipo_ubicacion($tipo_inventario = '')
	{
		$this->load->model('inventario_model');
		$arr_ubic = $this->inventario_model->get_combo_tipos_ubicacion($tipo_inventario);
		echo (json_encode($arr_ubic));
	}


	/**
	 * Administrador de los inventario activos
	 * @param  integer $pag numero de página a desplegar
	 * @return nada
	 */
	public function inventario($pag = 0)
	{
		$this->load->model('inventario_model');

		$this->load->library('pagination');
		$limite_por_pagina = 15;
		$config_pagination = array(
									'total_rows'  => $this->inventario_model->total_inventarios_activos(),
									'per_page'    => $limite_por_pagina,
									'base_url'    => site_url('config/inventario'),
									'uri_segment' => 3,
									'num_links'   => 5,
									'first_link'  => 'Primero',
									'last_link'   => 'Ultimo',
									'next_link'   => '<img src="'. base_url() . 'img/ic_right.png" />',
									'prev_link'   => '<img src="'. base_url() . 'img/ic_left.png" />',
								);
		$this->pagination->initialize($config_pagination);

		$datos_hoja = $this->inventario_model->get_inventarios_activos($limite_por_pagina, $pag);

		if ($this->input->post('formulario')=='inventario_activo')
		{
			foreach($datos_hoja as $reg)
			{
				$this->form_validation->set_rules($reg['id'].'-nombre', 'Nombre', 'trim|required');
				$this->form_validation->set_rules($reg['id'].'-tipo', 'Tipo', 'trim');
				$this->form_validation->set_rules($reg['id'].'-activo', 'Activo', 'trim');
			}			
		}
		else if ($this->input->post('formulario')=='agregar')
		{
			$this->form_validation->set_rules('agr_nombre', 'Nombre', 'trim|required');
			$this->form_validation->set_rules('agr-tipo', 'Tipo', 'trim');
			$this->form_validation->set_rules('agr-activo', 'Activo', 'trim');
		}
		else if ($this->input->post('formulario')=='borrar')
		{
			$this->form_validation->set_rules('id_borrar', '', 'trim|required');
		}

		$this->form_validation->set_error_delimiters('<div class="error round">', '</div>');
		$this->form_validation->set_message('required', 'Ingrese un valor para %s');

		if ($this->form_validation->run() == FALSE)
		{
			$data = array(
					'datos_hoja'           => $datos_hoja,
					'arr_tipos_inventario' => $this->inventario_model->get_combo_tipos_inventario(),					
					'msg_alerta'           => $this->session->flashdata('msg_alerta'),
					'links_paginas'        => $this->pagination->create_links(),
				);

			$this->_render_view('inventario_activo', $data);
		}
		else
		{
			if ($this->input->post('formulario') == 'inventario_activo')
			{
				$cant_modif = 0;

				foreach($datos_hoja as $reg)
				{
					if ((set_value($reg['id'].'-nombre') != $reg['nombre']) or 
						(set_value($reg['id'].'-tipo')   != $reg['tipo']) or
						(((set_checkbox($reg['id'].'-activo',1,FALSE) == '') ? 0 : 1) != $reg['activo']))
					{
						$this->inventario_model->guardar_inventario_activo($reg['id'], set_value($reg['id'].'-nombre'), set_value($reg['id'].'-tipo'), ((set_checkbox($reg['id'].'-activo',1,FALSE) == '') ? 0 : 1));
						$cant_modif += 1;
					}
				}
				$this->session->set_flashdata('msg_alerta', (($cant_modif > 0) ? $cant_modif . ' inventario(s) modificados correctamente' : ''));
			}
			else if ($this->input->post('formulario') == 'agregar')
			{
				$this->inventario_model->guardar_inventario_activo(0, set_value('agr_nombre'), set_value($reg['id'].'-tipo'), set_value('agr_activo'));
				$this->session->set_flashdata('msg_alerta', 'Inventario (' . set_value('agr_nombre') . ') agregado correctamente');
			}
			else if ($this->input->post('formulario') == 'borrar')
			{
				if ($this->inventario_model->get_cant_registros_inventario(set_value('id_borrar')) > 0)
				{
					$this->session->set_flashdata('msg_alerta', 'Inventario (id=' . set_value('id_borrar') . ') no está vacio, por lo que no se puede borrar');
				}
				else
				{
					$this->inventario_model->borrar_inventario_activo(set_value('id_borrar'));
					$this->session->set_flashdata('msg_alerta', 'Inventario (id=' . set_value('id_borrar') . ') borrado correctamente');
				}
			}
			redirect('config/inventario/' . $pag);
		}
	}


	public function sube_stock($id_inventario = 0)
	{
		$this->load->model('inventario_model');

		$upload_error = '';
		$script_carga = '';
		$regs_OK      = 0;
		$regs_error   = 0;
		$msj_error    = '';

		if ($this->input->post('formulario') == 'upload') 
		{

			if ($this->input->post('password') == 'logistica2012')
			{
				$upload_config = array(
					'upload_path'   => './upload/',
					'allowed_types' => 'txt|cvs',
					'max_size'      => '2000',
					'file_name'     => 'sube_stock.txt',
					'overwrite'     => true,
					);
				$this->load->library('upload', $upload_config);

				if (! $this->upload->do_upload('upload_file'))
				{
					$upload_error = '<br><div class="error round">' . $this->upload->display_errors() . '</div>';
				}
				else
				{
					$upload_data = $this->upload->data();
					$archivo_cargado = $upload_data['full_path'];

					$this->inventario_model->borrar_inventario($id_inventario);

					$res_procesa_archivo = $this->_procesa_archivo_cargado($id_inventario, $archivo_cargado);

					$script_carga = $res_procesa_archivo['script'];
					$regs_OK      = $res_procesa_archivo['regs_OK'];
					$regs_error   = $res_procesa_archivo['regs_error'];
					$msj_error    = ($regs_error > 0) ? '<br><div class="error round">' . $res_procesa_archivo['msj_termino'] . '</div>' : '';
				}
			}
		}

		$data = array(
				'inventario_id'     => $id_inventario,
				'inventario_nombre' => $this->inventario_model->get_nombre_inventario($id_inventario),
				'upload_error'      => $upload_error,
				'script_carga'      => $script_carga,
				'regs_OK'           => $regs_OK,
				'regs_error'        => $regs_error,
				'msj_error'         => $msj_error,
				'link_config'       => 'config',
				'link_reporte'      => 'reportes',
				'link_inventario'   => 'inventario',
				'msg_alerta'        => $this->session->flashdata('msg_alerta'),
			);

		$this->_render_view('sube_stock', $data);

	}


	public function inserta_linea_archivo()
	{
		$this->load->model('inventario_model');
		$this->inventario_model->guardar(
			$this->input->post('id'),
			$this->input->post('id_inv'),
			$this->input->post('hoja'),
			$this->input->post('aud'),
			$this->input->post('dig'),
			$this->input->post('ubic'),
			$this->input->post('cat'),
			$this->input->post('desc'),
			$this->input->post('lote'),
			$this->input->post('cen'),
			$this->input->post('alm'),
			$this->input->post('um'),
			$this->input->post('ssap'),
			$this->input->post('sfis'),
			$this->input->post('obs'),
			$this->input->post('fec'),
			$this->input->post('nvo')
			);


	}


	private function _procesa_archivo_cargado($id_inventario = '', $archivo = '')
	{
		$count_OK    = 0;
		$count_error = 0;
		$num_linea   = 0;
		$c           = 0;
		$arr_lineas_error = array();
		$arr_bulk_insert  = array();
		$script_carga = '';
		$fh = fopen($archivo, 'r');
		if ($fh)
		{
			while ($linea = fgets($fh))
			{
				$c += 1;
				$num_linea += 1;
				$resultado_procesa_linea = $this->_procesa_linea($id_inventario, $linea);
				if ($resultado_procesa_linea == 'no_procesar')
				{

				}
				elseif ($resultado_procesa_linea == 'error')
				{
					$count_error += 1;
					array_push($arr_lineas_error, $num_linea);
				}
				else
				{
					$count_OK += 1;
					if ($resultado_procesa_linea != '')
					{
						$script_carga .= 'proc_linea_carga(' . $c . ',' . $resultado_procesa_linea . ');' . "\n";
					}
				}
			}
			fclose($fh);
		}

		$msj_termino = 'Total lineas: ' . ($count_OK + $count_error) . ' (OK: ' . $count_OK . '; Error: ' . $count_error . ')'; 
		if ($count_error > 0)
		{
			$msj_termino .= '<br>Lineas con errores (';
			foreach ($arr_lineas_error as $key => $lin_error)
			{
				if ($key > 0)
				{
					$msj_termino .= ', ';
				}
				$msj_termino .= $lin_error;
			}
			$msj_termino .= ')'; 
		}

		return array('script' => $script_carga, 'regs_OK' => $count_OK, 'regs_error' => $count_error, 'msj_termino' => $msj_termino);

	}

	private function _procesa_linea($id_inventario = '', $linea = '')
	{
		$arr_linea = explode("\r", $linea);
		if ($arr_linea[0] != '')
		{
			$arr_datos = explode("\t", $arr_linea[0]);

			if (count($arr_datos) == 9)
			{
				$ubicacion   = trim(str_replace("'", '"', $arr_datos[0]));
				$catalogo    = trim(str_replace("'", '"', $arr_datos[1]));
				$descripcion = trim(str_replace("'", '"', $arr_datos[2]));
				$lote        = trim(str_replace("'", '"', $arr_datos[3]));
				$centro      = trim(str_replace("'", '"', $arr_datos[4]));
				$almacen     = trim(str_replace("'", '"', $arr_datos[5]));
				$um          = trim(str_replace("'", '"', $arr_datos[6]));
				$stock_sap   = trim($arr_datos[7]);
				$hoja        = trim($arr_datos[8]);

				if ($ubicacion == 'UBICACION' or $catalogo == 'CATALOGO' or $centro    == 'CENTRO' or 
					$almacen   == 'ALMACEN'   or $lote     == 'LOTE'     or $um        == 'UM'     or 
					$stock_sap == 'STOCK_SAP' or $hoja     == 'HOJA')
				{
					// cabecera del archivo, no se hace nada
					return 'no_procesar';
				}
				else
				{
					if (is_numeric($stock_sap) and is_numeric($hoja))
					{
						return (
							'0,' .
							$id_inventario      . ',' . 
							$hoja               . ',' .
							'0,' .
							'0,' .
							'\'' . $ubicacion   . '\',' .
							'\'' . $catalogo    . '\',' .
							'\'' . $descripcion . '\',' .
							'\'' . $lote        . '\',' .
							'\'' . $centro      . '\',' .
							'\'' . $almacen     . '\',' .
							'\'' . $um          . '\',' .
							$stock_sap          . ',' .
							'0,' .
							'\'\',' .
							'\'' . date('Y-d-m H:i:s') . '\',' .
							'\'\''
							);

					}
					else
					{
						// error: stock y/o hoja no son numericos
						return 'error';
					}
				}

			}
			else
			{
				// error: linea con cantidad de campos <> 9
				return 'error';
			}
		}
		else
		{
			// no error: linea en blanco
			return 'no_procesar';
		}
	}

	public function imprime_inventario($id_inventario = 0)
	{
		$this->load->model('inventario_model');

		$this->form_validation->set_rules('pag_desde', 'Pagina Desde', 'trim|required|greater_than[0]');
		$this->form_validation->set_rules('pag_hasta', 'Pagina Hasta', 'trim|required|greater_than[0]');
		$this->form_validation->set_rules('oculta_stock_sap', 'Oculta Stock SAP', '');
		$this->form_validation->set_error_delimiters('<div class="error round">', '</div>');
		$this->form_validation->set_message('required', 'Ingrese un valor para [%s]');
		$this->form_validation->set_message('greater_than', 'El valor del campo [%s] debe ser mayor o igual a 1');

		if ($this->form_validation->run() == FALSE)
		{
			$data = array(
					'inventario_id'     => $id_inventario,
					'inventario_nombre' => $this->inventario_model->get_nombre_inventario($id_inventario),
					'max_hoja'          => $this->inventario_model->get_max_hoja_inventario($id_inventario),
					'link_config'       => 'config',
					'link_reporte'      => 'reportes',
					'link_inventario'   => 'inventario',
					'msg_alerta'        => $this->session->flashdata('msg_alerta'),
				);

			$this->_render_view('imprime_inventario', $data);		
		}
		else
		{
			$oculta_stock_sap = (set_value('oculta_stock_sap') == 'oculta_stock_sap') ? 1 : 0;
			redirect("config/imprime_hojas/" . set_value('pag_desde') . '/' . set_value('pag_hasta') . '/' . $oculta_stock_sap . '/' . time());
		}

	}


	public function imprime_hojas($hoja_desde = 0, $hoja_hasta = 0, $oculta_stock_sap = 0)
	{

		if ($hoja_hasta == 0 or $hoja_hasta < $hoja_desde)
		{
			$hoja_hasta = $hoja_desde;
		}

		$this->load->model('inventario_model');

		// recupera el inventario activo
		$this->id_inventario = $this->inventario_model->get_id_inventario_activo();
		$nombre_inventario = $this->inventario_model->get_nombre_inventario($this->id_inventario);

		$this->load->view('inventario_print_head');

		for($hoja = $hoja_desde; $hoja <= $hoja_hasta; $hoja++)
		{
			$datos_hoja        = $this->inventario_model->get_hoja($this->id_inventario, $hoja);
			$data = array(
					'datos_hoja'        => $datos_hoja,
					'oculta_stock_sap'  => $oculta_stock_sap,
					'hoja'              => $hoja,
					'nombre_inventario' => $nombre_inventario,
				);			

			$this->load->view('inventario_print_body', $data);
		}

		$this->load->view('inventario_print_footer');

	}


	private function _render_view($vista = '', $data = array()) 
	{
		$data['titulo_modulo'] = 'Configuracion';
		$this->load->view('app_header', $data);
		$this->load->view($vista, $data);
		$this->load->view('app_footer', $data);
	}


}

/* End of file config.php */
/* Location: ./application/controllers/config.php */
