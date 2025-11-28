 # **README.md**

# Sistema de Gestión de Citas Médicas

Este proyecto es un sistema web diseñado para facilitar la gestión de citas médicas entre pacientes, doctores y administradores. La plataforma está construida utilizando un _stack_ de tecnologías web estándar, que incluye **PHP** para el _backend_, **MySQL** como base de datos, y **HTML**, **CSS**, **JavaScript** y **Bootstrap** para el desarrollo del _frontend_. 

---

## Funcionalidades Principales

### **Panel de Administración (Acceso de Administrador)**

El administrador tiene control total sobre el sistema y es responsable de la configuración y el mantenimiento.

* **_Dashboard_ de Estadísticas:** Visualiza estadísticas generales y semanales para monitorear la actividad de la plataforma, así como las próximas citas.
* **Gestión de Doctores:** Permite **crear cuentas para nuevos doctores**, ya que ellos no pueden registrarse por sí mismos. Se requiere la siguiente información para cada doctor:
    * Nombre Completo
    * Usuario
    * Correo Electrónico
    * Contraseña
    * Años de Experiencia
    * Descripción Profesional
    * Especialidad
    * Disponibilidad Semanal (horarios en intervalos de 20 minutos; cada cita dura 1 hora).
* **Gestión de Usuarios:** Muestra una tabla con todos los usuarios registrados. El administrador puede **cambiar sus contraseñas** y **eliminar sus cuentas**.
* **Gestión de Feriados:** Permite designar fechas específicas como feriados para evitar que los pacientes reserven citas en esos días. Se debe indicar la **fecha** y el **motivo**.

---

### **Panel del Paciente (Acceso de Paciente)**

Los pacientes pueden gestionar sus citas y encontrar doctores.

* **Ver Citas:** Los pacientes pueden ver todas las citas que han programado.
* **Reservar Citas:**
    * La reserva se realiza con el nombre del paciente.
    * Se elige una **especialidad**.
    * Se selecciona un **doctor** disponible dentro de la especialidad elegida.
    * Se elige una **fecha** (no más de 3 meses en el futuro).
    * Opcionalmente, se puede incluir el **motivo de la consulta**.

---

### **Panel del Doctor (Acceso de Doctor)**

Los doctores pueden gestionar sus pacientes y las consultas.

* **Gestión de Pacientes y Citas:**
    * El doctor puede ver una lista de sus pacientes y las citas programadas.
    * Para cada cita, puede **confirmarla**, **cancelarla** (indicando un motivo) o **enviar una ficha médica**.
* **Fichas Médicas:**
    * El doctor puede crear una ficha para el paciente que incluya un **diagnóstico** y un **tratamiento**.
    * La ficha se puede enviar al paciente.
* **Historial de Fichas:**
    * Debajo de la información de cada paciente, se muestra un historial de todas las fichas médicas enviadas previamente.
