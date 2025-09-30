-- Homogenizar Medicina Interna y Oftalmología
USE clinica_moya;

-- Actualizar Medicina Interna (con mayúsculas)
UPDATE specialties SET name = 'Medicina Interna' WHERE name = 'Medicina interna';

-- Actualizar Oftalmología (con tilde)
UPDATE specialties SET name = 'Oftalmología' WHERE name = 'Oftalmologia';

-- Verificar cambios
SELECT id, name, description FROM specialties ORDER BY name;
