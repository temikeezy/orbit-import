<?php
namespace OUI\Admin;

defined( 'ABSPATH' ) || exit;

class Admin {
	public function render() {
		$step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : 'upload';
		$job_id = isset( $_GET['job_id'] ) ? (int) $_GET['job_id'] : 0;
		echo '<div class="wrap"><h1>' . esc_html__( 'ORBIT Import', 'orbit-import' ) . '</h1>';
		echo '<h2 class="nav-tab-wrapper">';
		$this->tab( 'upload', $step, $job_id );
		$this->tab( 'map', $step, $job_id );
		$this->tab( 'dry-run', $step, $job_id );
		$this->tab( 'run', $step, $job_id );
		$this->tab( 'report', $step, $job_id );
		echo '</h2>';
		switch ( $step ) {
			case 'map': $this->view( 'map', $job_id ); break;
			case 'dry-run': $this->view( 'dry-run', $job_id ); break;
			case 'run': $this->view( 'run', $job_id ); break;
			case 'report':
				$this->view( 'report', $job_id );
				$this->jobs_table();
				break;
			case 'upload':
			default: $this->view( 'upload', $job_id );
		}
		echo '</div>';
	}

	private function tab( $slug, $current, $job_id ) {
		$url = admin_url( 'users.php?page=orbit-import&step=' . $slug . ( $job_id ? '&job_id=' . (int) $job_id : '' ) );
		$cls = $slug === $current ? ' nav-tab nav-tab-active' : ' nav-tab';
		echo '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( $url ) . '">' . esc_html( ucwords( str_replace( '-', ' ', $slug ) ) ) . '</a>';
	}

	private function view( $name, $job_id ) {
		$file = plugin_dir_path( dirname( __DIR__ ) ) . 'includes/Admin/views/' . $name . '.php';
		if ( file_exists( $file ) ) { include $file; }
	}

	private function jobs_table() {
		$table = new Jobs_Table();
		$table->prepare_items();
		echo '<h2>' . esc_html__( 'Jobs', 'orbit-import' ) . '</h2>';
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="orbit-import" />';
		$table->display();
		echo '</form>';
	}
}
