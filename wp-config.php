<?php
/** 
 * Configuración básica de WordPress.
 *
 * Este archivo contiene las siguientes configuraciones: ajustes de MySQL, prefijo de tablas,
 * claves secretas, idioma de WordPress y ABSPATH. Para obtener más información,
 * visita la página del Codex{@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} . Los ajustes de MySQL te los proporcionará tu proveedor de alojamiento web.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** Ajustes de MySQL. Solicita estos datos a tu proveedor de alojamiento web. ** //
/** El nombre de tu base de datos de WordPress */
define('DB_NAME', 'wordpress');

/** Tu nombre de usuario de MySQL */
define('DB_USER', 'wordpress');

/** Tu contraseña de MySQL */
define('DB_PASSWORD', 'wordpress');

/** Host de MySQL (es muy probable que no necesites cambiarlo) */
define('DB_HOST', 'localhost');

/** Codificación de caracteres para la base de datos. */
define('DB_CHARSET', 'utf8');

/** Cotejamiento de la base de datos. No lo modifiques si tienes dudas. */
define('DB_COLLATE', '');

/**#@+
 * Claves únicas de autentificación.
 *
 * Define cada clave secreta con una frase aleatoria distinta.
 * Puedes generarlas usando el {@link https://api.wordpress.org/secret-key/1.1/salt/ servicio de claves secretas de WordPress}
 * Puedes cambiar las claves en cualquier momento para invalidar todas las cookies existentes. Esto forzará a todos los usuarios a volver a hacer login.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', '=y>[&P7j|xxXN`.,c}<#tx*yDjsCCS2?{V<ovq(8=YI33-!bB(mhHT<L?p>WqB5;'); // Cambia esto por tu frase aleatoria.
define('SECURE_AUTH_KEY', '=_J~7;]Xo8C&LfUm(lAp`Cr(TG|O8j~CP{Y|;J`W%|+.NopYyY(wqx1=>nH~QKJ3'); // Cambia esto por tu frase aleatoria.
define('LOGGED_IN_KEY', ' ut{.fOd@ud,D_Mt/0l3vV/_;?:[v7`|8r*Ygk0}@3l5-PF]q]*+bOEU=?Y$llf@'); // Cambia esto por tu frase aleatoria.
define('NONCE_KEY', 'fs|VjATePzCe+t:_-$nt4b1c&%Gz6U-nGd2Uqs!:-i.ZPe4Pq[IuO=zA#2HX>qaG'); // Cambia esto por tu frase aleatoria.
define('AUTH_SALT', 'S.X)cFDIuqQr|/%YNa+$j4005q-wfA8i8O4%J``;!N70bxI{2]*KIl>5STOL~%gJ'); // Cambia esto por tu frase aleatoria.
define('SECURE_AUTH_SALT', 'P3&+}g$o`8|uSL(|*+hoI#c&!=D(_N{Oa^DB!u8!Ch8lU 4P(KE[xk4>-(&$zJ$-'); // Cambia esto por tu frase aleatoria.
define('LOGGED_IN_SALT', 'z6Tp3[+:!4k%(>$VJXcO}6q(Z@:<$vh2<]j@P=EhG3k1D oGM(])EW^RpgVWgWO&'); // Cambia esto por tu frase aleatoria.
define('NONCE_SALT', 'G4klJ=ZB}?h+BpeuMO,flle=N!0+04x:@O3iC^zB 5F)!|<vCo=&;&V5er1e_5_0'); // Cambia esto por tu frase aleatoria.

/**#@-*/

/**
 * Prefijo de la base de datos de WordPress.
 *
 * Cambia el prefijo si deseas instalar multiples blogs en una sola base de datos.
 * Emplea solo números, letras y guión bajo.
 */
$table_prefix  = 'wp_';

/**
 * Idioma de WordPress.
 *
 * Cambia lo siguiente para tener WordPress en tu idioma. El correspondiente archivo MO
 * del lenguaje elegido debe encontrarse en wp-content/languages.
 * Por ejemplo, instala ca_ES.mo copiándolo a wp-content/languages y define WPLANG como 'ca_ES'
 * para traducir WordPress al catalán.
 */
define('WPLANG', 'es_ES');

/**
 * Para desarrolladores: modo debug de WordPress.
 *
 * Cambia esto a true para activar la muestra de avisos durante el desarrollo.
 * Se recomienda encarecidamente a los desarrolladores de temas y plugins que usen WP_DEBUG
 * en sus entornos de desarrollo.
 */
define('WP_DEBUG', false);

/* ¡Eso es todo, deja de editar! Feliz blogging */

/** WordPress absolute path to the Wordpress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

