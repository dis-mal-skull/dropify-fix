# WooCommerce Dropi Integration - Fix Plugin

## 🚀 Descripción

Este plugin es un **fix que soluciona los problemas críticos** del plugin oficial de Dropi (dropshipongq) que presentaba errores graves al cargar imágenes de productos, las cuales aparecían en blanco o no se cargaban correctamente en WooCommerce.

## 🔧 Problemas que soluciona

- ✅ **Carga de imágenes en blanco**: Corrige el error donde las imágenes de productos aparecían vacías
- ✅ **Errores de sincronización**: Soluciona fallos en la importación de productos desde Dropi
- ✅ **Integración mejorada**: Optimiza la comunicación entre Dropi y WooCommerce
- ✅ **Estabilidad**: Mejora la estabilidad general del proceso de importación

## 📋 Características

- Integración completa con WooCommerce
- Sincronización automática de productos
- Gestión de inventario en tiempo real
- Soporte para múltiples países (Colombia, Chile, Ecuador, México, Panamá)
- Interfaz de administración intuitiva
- Sistema de logs para debugging

## 🛠️ Instalación

1. Descarga el plugin desde este repositorio
2. Sube el archivo ZIP a tu WordPress en `Plugins > Añadir nuevo > Subir plugin`
3. Activa el plugin
4. Configura tus credenciales de Dropi en `WooCommerce > Dropi Integration`

## ⚙️ Configuración

1. Ve a **WooCommerce > Dropi Integration**
2. Ingresa tu API Key de Dropi
3. Configura las opciones de sincronización
4. Selecciona los países de operación
5. Guarda la configuración

## 📁 Estructura del Plugin

```
wc-dropi-integration/
├── clasess/
│   ├── Dropi.php           # Clase principal de integración
│   ├── Products.php        # Gestión de productos
│   ├── Settings.php        # Configuraciones
│   ├── Helper.php          # Funciones auxiliares
│   └── StatesPlaces.php    # Gestión de ubicaciones
├── css/                    # Estilos
├── js/                     # Scripts JavaScript
├── img/                    # Imágenes del plugin
└── places/                 # Archivos de países y estados
```

## 🐛 Debugging

El plugin incluye un sistema de logs que puedes encontrar en:
- WordPress Admin > WooCommerce > Estado > Logs
- Busca logs con prefijo "dropi-integration"

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Fork el repositorio
2. Crea una rama para tu feature (`git checkout -b feature/nueva-caracteristica`)
3. Commit tus cambios (`git commit -am 'Añade nueva característica'`)
4. Push a la rama (`git push origin feature/nueva-caracteristica`)
5. Crea un Pull Request

## 📝 Changelog

### v1.0.0
- Fix inicial para problemas de carga de imágenes
- Corrección de errores de sincronización
- Mejoras en la estabilidad general

## 👨‍💻 Desarrollador

**Camilo Osorio**  
📧 dismal.creative@gmail.com

## 📄 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

---

⚠️ **Nota**: Este es un fix no oficial del plugin de Dropi. Úsalo bajo tu propia responsabilidad y siempre haz backups antes de instalar.
