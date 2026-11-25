<?php
// core/Evidencia.php
// Firma digital (PNG en base64) y foto de evidencia: validacion de imagen real, tamano y nombre de archivo
// sin entrada del usuario. Compartido por EntregaController y PrestamoController.
// Flujo en dos fases: primero se validan TODAS las evidencias (validarFirma/validarFoto) y despues se escriben (guardar).

require_once __DIR__ . '/Money.php';

class Evidencia {

    const RUTA_FIRMAS = '/uploads/firmas';
    const RUTA_EVIDENCIAS = '/uploads/evidencias';

    /** Valida la firma enviada en $_POST['firma_base64']. Devuelve los bytes del PNG o null si no se envio. */
    public static function validarFirma() {
        if (empty($_POST['firma_base64'])) {
            return null;
        }
        $max = (int)Money::config('fondos.firma_max_bytes', 1048576);
        list($bytes, ) = self::decodificarImagenBase64((string)$_POST['firma_base64'], array('image/png'), $max, 'firma digital');
        return $bytes;
    }

    /**
     * Valida la foto de evidencia: archivo subido ($_FILES['foto_evidencia']) o base64 ($_POST['foto_base64']).
     * Devuelve array('tmp' => ruta|null, 'bytes' => bytes|null, 'ext' => extension) o null si no se envio.
     */
    public static function validarFoto() {
        $max = (int)Money::config('fondos.foto_max_bytes', 5242880);
        $mimes = (array)Money::config('fondos.foto_tipos', array('image/jpeg', 'image/png', 'image/webp'));

        if (!empty($_FILES['foto_evidencia']['tmp_name'])) {
            $f = $_FILES['foto_evidencia'];
            if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
                throw new RuntimeException("No se pudo recibir la foto de evidencia.");
            }
            if ((int)$f['size'] > $max) {
                throw new RuntimeException("La foto de evidencia supera el tamaño permitido (" . round($max / 1048576, 1) . " MB).");
            }
            $info = @getimagesize($f['tmp_name']);
            if ($info === false || !in_array($info['mime'], $mimes, true)) {
                throw new RuntimeException("La foto de evidencia no es una imagen válida (JPG, PNG o WEBP).");
            }
            return array('tmp' => $f['tmp_name'], 'bytes' => null, 'ext' => self::extensionDeMime($info['mime']));
        }
        if (!empty($_POST['foto_base64'])) {
            list($bytes, $ext) = self::decodificarImagenBase64((string)$_POST['foto_base64'], $mimes, $max, 'foto de evidencia');
            return array('tmp' => null, 'bytes' => $bytes, 'ext' => $ext);
        }
        return null;
    }

    /**
     * Escribe la firma validada. Devuelve la ruta publica (para la BD) y agrega el archivo a $escritos
     * para poder borrarlo si luego falla el registro.
     */
    public static function guardarFirma($bytes, $socioId, $tipo, array &$escritos) {
        $dir = self::directorio('firmas');
        $nombre = self::nombreSeguro('firma', (int)$socioId, (string)$tipo, 'png');
        if (@file_put_contents($dir . '/' . $nombre, $bytes) === false) {
            throw new RuntimeException("No se pudo guardar la firma digital.");
        }
        $escritos[] = $dir . '/' . $nombre;
        return self::RUTA_FIRMAS . '/' . $nombre;
    }

    /** Escribe la foto validada (resultado de validarFoto). Devuelve la ruta publica. */
    public static function guardarFoto(array $foto, $socioId, $tipo, array &$escritos) {
        $dir = self::directorio('evidencias');
        $nombre = self::nombreSeguro('foto', (int)$socioId, (string)$tipo, $foto['ext']);
        $ok = ($foto['tmp'] !== null)
            ? @move_uploaded_file($foto['tmp'], $dir . '/' . $nombre)
            : (@file_put_contents($dir . '/' . $nombre, $foto['bytes']) !== false);
        if (!$ok) {
            throw new RuntimeException("No se pudo guardar la foto de evidencia.");
        }
        $escritos[] = $dir . '/' . $nombre;
        return self::RUTA_EVIDENCIAS . '/' . $nombre;
    }

    /**
     * Valida y decodifica una imagen en base64 (data URI). Devuelve [bytes, extension] o lanza RuntimeException.
     * @param string[] $mimesPermitidos
     */
    public static function decodificarImagenBase64($dataUri, array $mimesPermitidos, $maxBytes, $nombre) {
        if (!preg_match('#^data:(image/[a-z0-9.+-]+);base64,#i', $dataUri, $m)) {
            throw new RuntimeException("La {$nombre} no es una imagen válida.");
        }
        $bytes = base64_decode(substr($dataUri, strlen($m[0])), true);
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException("La {$nombre} no se pudo decodificar.");
        }
        if (strlen($bytes) > $maxBytes) {
            throw new RuntimeException("La {$nombre} supera el tamaño permitido (" . round($maxBytes / 1048576, 1) . " MB).");
        }
        $info = @getimagesizefromstring($bytes);
        if ($info === false || empty($info[0]) || empty($info[1]) || !in_array($info['mime'], $mimesPermitidos, true)) {
            throw new RuntimeException("La {$nombre} no es una imagen real de un tipo permitido.");
        }
        return array($bytes, self::extensionDeMime($info['mime']));
    }

    public static function extensionDeMime($mime) {
        $mapa = array('image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp');
        return $mapa[$mime] ?? 'bin';
    }

    /** El nombre no incluye texto del usuario; el sufijo aleatorio evita colisiones y nombres adivinables. */
    public static function nombreSeguro($prefijo, $socioId, $tipo, $ext) {
        return $prefijo . '_' . (int)$socioId . '_' . strtolower($tipo) . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    }

    /** Carpeta fisica (public/uploads/firmas o public/uploads/evidencias); la crea si no existe. */
    private static function directorio($cual) {
        $dir = __DIR__ . '/../public/uploads/' . $cual;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }
}
