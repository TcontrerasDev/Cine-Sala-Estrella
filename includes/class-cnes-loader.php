<?php
/**
 * Register and run all hooks for the plugin.
 *
 * @package SalaEstrellaManager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Collects actions and filters, then registers them all with WordPress.
 */
class CNES_Loader {

	/** @var array Registered actions. */
	protected $actions = array();

	/** @var array Registered filters. */
	protected $filters = array();

	/**
	 * Add an action hook.
	 *
	 * @param string $hook      WordPress hook name.
	 * @param object $component Object instance that owns the callback.
	 * @param string $callback  Method name on $component.
	 * @param int    $priority  Hook priority.
	 * @param int    $args      Number of accepted arguments.
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $args );
	}

	/**
	 * Add a filter hook.
	 *
	 * @param string $hook      WordPress hook name.
	 * @param object $component Object instance that owns the callback.
	 * @param string $callback  Method name on $component.
	 * @param int    $priority  Hook priority.
	 * @param int    $args      Number of accepted arguments.
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $args );
	}

	/**
	 * Append a hook entry to the given array.
	 *
	 * @param array  $hooks     Existing hooks array.
	 * @param string $hook      Hook name.
	 * @param object $component Callback owner.
	 * @param string $callback  Method name.
	 * @param int    $priority  Priority.
	 * @param int    $args      Arg count.
	 * @return array
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $args ) {
		$hooks[] = array(
			'hook'      => $hook,
			'component' => $component,
			'callback'  => $callback,
			'priority'  => $priority,
			'args'      => $args,
		);
		return $hooks;
	}

	/**
	 * Register all collected hooks with WordPress.
	 */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['args']
			);
		}

		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['args']
			);
		}
	}
}
