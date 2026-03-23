<?php
// =============================================================
// src/Core/Router.php - موجه الطلبات (Simple Router)
// =============================================================

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private string $prefix = '';
    private array $groupMiddleware = [];

    /**
     * تسجيل مسار GET
     */
    public function get(string $path, array|string|callable $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * تسجيل مسار POST
     */
    public function post(string $path, array|string|callable $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * تسجيل مسار يقبل GET و POST
     */
    public function any(string $path, array|string|callable $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * مجموعة مسارات مع بادئة و middleware مشتركة
     */
    public function group(array $options, callable $callback): void
    {
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->prefix .= ($options['prefix'] ?? '');
        $this->groupMiddleware = array_merge(
            $this->groupMiddleware,
            (array)($options['middleware'] ?? [])
        );

        $callback($this);

        $this->prefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    /**
     * إضافة middleware عام
     */
    public function addMiddleware(string $name, callable $handler): void
    {
        $this->middleware[$name] = $handler;
    }

    /**
     * تنفيذ الطلب الحالي
     */
    public function dispatch(string $method, string $uri): mixed
    {
        $method = strtoupper($method);

        // إزالة query string
        $uri = strtok($uri, '?');
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $pattern = $this->buildPattern($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                // تنفيذ middleware
                foreach ($route['middleware'] as $mw) {
                    if (isset($this->middleware[$mw])) {
                        $result = ($this->middleware[$mw])();
                        if ($result === false) {
                            return null;
                        }
                    }
                }

                // استخراج المعاملات
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                return $this->callHandler($route['handler'], $params);
            }
        }

        // 404
        http_response_code(404);
        if ($this->isApiRequest($uri)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'المسار غير موجود'], JSON_UNESCAPED_UNICODE);
        } else {
            include __DIR__ . '/../../error/404.php';
        }
        return null;
    }

    private function addRoute(string $method, string $path, array|string|callable $handler): self
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->prefix . $path,
            'handler' => $handler,
            'middleware' => $this->groupMiddleware,
        ];
        return $this;
    }

    private function buildPattern(string $path): string
    {
        // تحويل {param} إلى regex named groups
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function callHandler(array|string|callable $handler, array $params): mixed
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $method] = $handler;
            $container = Container::getInstance();
            $controller = $container->has($controllerClass)
                ? $container->make($controllerClass)
                : new $controllerClass();
            return call_user_func_array([$controller, $method], $params);
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$controllerClass, $method] = explode('@', $handler);
            $controller = new $controllerClass();
            return call_user_func_array([$controller, $method], $params);
        }

        return null;
    }

    private function isApiRequest(string $uri): bool
    {
        return str_starts_with($uri, '/api/') || str_starts_with($uri, '/api');
    }
}
