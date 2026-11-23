---aqui lo que hace es Ejecutar una vez sobre la base existente después de verificar que
-- eventos.categoria_id apunta a la categoría correcta de cada evento.
ALTER TABLE eventos
  DROP COLUMN categoria;
