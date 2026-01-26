<?php
/**
 * Sistema VISTA - Front Controller Público
 * Roteador que funciona SEM necessidade de configuração do servidor
 */

// Carrega o sistema de roteamento
require_once __DIR__ . '/router.php';

// Cria e executa o roteador
$router = createRouter();
$router->dispatch();
// --- Mobile redirect logic for inventory view ---------------------------------
// Rules:
// - If request is GET and ?url=inventario, perform UA-based redirect to inventario_mobile
// - Preserve all query parameters
// - Respect explicit override via ?force_view=desktop|mobile
// - Do not redirect when URL already points to inventario_mobile (avoid loops)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
	$requested = isset($_GET['url']) ? trim($_GET['url']) : '';
	$requestedLower = strtolower($requested);

	// Skip if already explicitly requesting mobile/desktop specific view
	if ($requestedLower === 'inventario') {
		$force = isset($_GET['force_view']) ? strtolower(trim($_GET['force_view'])) : null;

		$shouldRedirect = false;

		if ($force === 'desktop') {
			$shouldRedirect = false; // explicit desktop requested
		} elseif ($force === 'mobile') {
			$shouldRedirect = true; // explicit mobile requested
		} else {
			// simple UA sniffing
			$ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
			if ($ua !== '') {
				if (strpos($ua, 'android') !== false || strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false || strpos($ua, 'mobile') !== false) {
					$shouldRedirect = true;
				}
			}
		}

		if ($shouldRedirect) {
			// preserve all query params but replace url
			$qs = $_GET;
			$qs['url'] = 'inventario_mobile';
			$location = '/router_public.php?' . http_build_query($qs);
			header('Location: ' . $location);
			exit;
		}
	}
}
