-- Actualizar especialidades de Clínica Moya (versión segura)
USE clinica_moya;

-- Paso 1: Verificar si hay doctores con Radiología
SELECT d.user_id, u.full_name, s.name as especialidad
FROM doctor_specialty ds
JOIN doctors d ON d.user_id = ds.doctor_id
JOIN users u ON u.id = d.user_id
JOIN specialties s ON s.id = ds.specialty_id
WHERE s.name = 'Radiologia';

-- Paso 2: Eliminar relaciones de doctores con Radiología
-- (Si hay doctores con Radiología, primero se eliminan de la tabla intermedia)
DELETE FROM doctor_specialty 
WHERE specialty_id = (SELECT id FROM specialties WHERE name = 'Radiologia');

-- Paso 3: Ahora sí eliminar Radiología
DELETE FROM specialties WHERE name = 'Radiologia';

-- Paso 4: Homogenizar nombres de especialidades (capitalización correcta)
UPDATE specialties SET name = 'Anestesiología', description = 'Especialidad médica dedicada al manejo del dolor y la anestesia' 
WHERE name = 'Anestesiologia';

UPDATE specialties SET name = 'Cardiología', description = 'Especialidad médica que se ocupa de las enfermedades del corazón' 
WHERE name = 'Cardiologia';

UPDATE specialties SET name = 'Dermatología', description = 'Especialidad médica que trata las enfermedades de la piel' 
WHERE name = 'Dermatologia';

UPDATE specialties SET name = 'Ginecología', description = 'Especialidad médica que trata el sistema reproductor femenino' 
WHERE name = 'Ginecologia';

UPDATE specialties SET name = 'Medicina Interna', description = 'Especialidad médica que se dedica a la atención integral del adulto' 
WHERE name = 'Medicina interna';

UPDATE specialties SET name = 'Oftalmología', description = 'Especialidad médica que trata las enfermedades de los ojos' 
WHERE name = 'Oftalmologia';

UPDATE specialties SET name = 'Psiquiatría', description = 'Especialidad médica dedicada al estudio y tratamiento de trastornos mentales' 
WHERE name = 'Psiquiatria';

-- Paso 5: Verificar especialidades actualizadas
SELECT id, name, description FROM specialties ORDER BY name;
