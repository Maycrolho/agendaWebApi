-- Ejecuta esta primera parte una sola vez sobre la base agenda.
-- Conserva los eventos existentes, crea usuarios y prepara su columna de dueño.
USE agenda;
CREATE TABLE eventos_respaldo_p8 AS SELECT * FROM eventos;

CREATE TABLE IF NOT EXISTS usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(80)  NOT NULL,
  correo     VARCHAR(120) NOT NULL UNIQUE,
  clave_hash VARCHAR(255) NOT NULL,
  creado_en  DATETIME     DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE eventos ADD COLUMN usuario_id INT NULL AFTER id;

-- Después de ejecutar lo anterior, crea tu cuenta desde Registro.php.
-- Revisa el id asignado con: SELECT id, nombre FROM usuarios;
-- Luego reemplaza 1 por ese id y ejecuta la segunda parte:

-- SET @usuario_id = 1;
-- UPDATE eventos SET usuario_id = @usuario_id WHERE usuario_id IS NULL;
-- ALTER TABLE eventos
--   MODIFY usuario_id INT NOT NULL,
--   ADD CONSTRAINT fk_eventos_usuario
--       FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
--       ON DELETE CASCADE;

-- Comprueba al terminar: DESCRIBE eventos;