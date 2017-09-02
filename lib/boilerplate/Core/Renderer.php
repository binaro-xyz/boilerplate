<?php

namespace boilerplate\Core;


class Renderer {
    private $twig_loader;
    private $twig_env;

    public function __construct(string $view_dir) {
        $this->twig_loader = new \Twig_Loader_Filesystem($view_dir);
        $this->twig_env = new \Twig_Environment($this->twig_loader);

        $this->twig_env->addFunction(new \Twig_Function('route', function(string $route_name, array $parameters = array(), $relative = true) {
            return Router::getRouteUrl($route_name, $parameters, $relative);
        }));
    }

    /**
     * Returns a rendered Twig view.
     *
     * @param string $view The name of the view to render (i.e. the path relative to FILES_DIR, e.g. `admin/index.html`)
     * @param array $variables An array of variables to pass to the view
     * @return string
     */
    public function render(string $view, array $variables = array()) : string {
        return $this->twig_env->render($view, $variables);
    }

    public function viewExists(string $view) : bool {
        return is_file(Application::instance()->config->get(ConfigurationOption::VIEW_DIR) . '/' . ltrim($view, '/'));
    }
}
