// Header.js (Modificado)
import React, { useState } from 'react'; // ¡Añadido useState!
import { motion, AnimatePresence } from 'framer-motion'; // ¡Añadido AnimatePresence!
import { History, Globe, ChevronDown, Check } from 'lucide-react'; // ¡Añadidos ChevronDown y Check!

// Lista de idiomas disponibles y sus códigos
const LANGUAGES = [
  { code: 'es', display: 'ES', name: 'Español', tooltip: 'Cambiar a Español' },
  { code: 'en', display: 'EN', name: 'English', tooltip: 'Switch to English' },
  { code: 'fr', display: 'FR', name: 'Français', tooltip: 'Passer au Français' },
  { code: 'pt', display: 'PT', name: 'Português', tooltip: 'Mudar para Português' },
];

// Aceptar las props onToggleHistory, currentLang, setLanguage y historyTooltip (nueva prop de App.js)
const Header = ({ onToggleHistory, currentLang, setLanguage, historyTooltip }) => {
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  
  // Encontrar la información del idioma actual para mostrarla en el botón
  const currentLangInfo = LANGUAGES.find(lang => lang.code === currentLang) || LANGUAGES[0]; // Fallback a Español
  const langTooltip = `${currentLangInfo.name} (Current: ${currentLangInfo.display})`; 

  // Función para manejar el cambio de idioma
  const handleLanguageChange = (code) => {
      setLanguage(code);
      setIsDropdownOpen(false); // Cierra el menú después de seleccionar
  }

  return (
    <motion.div
      className="bg-white shadow-lg rounded-2xl p-6 mb-8 flex items-center justify-between"
      initial={{ opacity: 0, y: -20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.6 }}
    >
      <h1 className="text-4xl font-bold bg-gradient-to-r from-blue-600 to-yellow-500 bg-clip-text text-transparent">
        Vision AI 
      </h1>
      
      {/* Contenedor para agrupar los botones */}
      <div className="flex space-x-4"> 
        
        {/* NUEVO: Botón de Selector de Idioma (con Dropdown) */}
        <div className="relative">
            <motion.button
                onClick={() => setIsDropdownOpen(prev => !prev)}
                className="p-3 rounded-full bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors shadow-md flex items-center group"
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
                aria-label={langTooltip}
                title={langTooltip}
            >
                <Globe className="w-6 h-6 mr-1" />
                <span className="font-bold text-sm">{currentLangInfo.display}</span>
                {/* Flecha que rota para indicar el estado del dropdown */}
                <ChevronDown className={`w-4 h-4 ml-1 transition-transform ${isDropdownOpen ? 'rotate-180' : 'rotate-0'}`} />
            </motion.button>

            {/* Dropdown de Idiomas */}
            <AnimatePresence>
                {isDropdownOpen && (
                    <motion.div
                        className="absolute right-0 top-full mt-2 w-48 bg-white rounded-lg shadow-xl z-50 overflow-hidden border border-gray-100"
                        initial={{ opacity: 0, y: -10 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: -10 }}
                        transition={{ duration: 0.2 }}
                    >
                        {LANGUAGES.map((lang) => (
                            <button
                                key={lang.code}
                                onClick={() => handleLanguageChange(lang.code)}
                                className={`w-full text-left px-4 py-2 text-sm flex items-center justify-between transition-colors ${
                                    currentLang === lang.code 
                                        ? 'bg-blue-500 text-white font-bold' 
                                        : 'text-gray-700 hover:bg-gray-100'
                                }`}
                            >
                                {lang.name}
                                {currentLang === lang.code && <Check className="w-4 h-4 ml-2" />}
                            </button>
                        ))}
                    </motion.div>
                )}
            </AnimatePresence>
        </div>


        {/* Botón para abrir el historial (usa el nuevo prop 'historyTooltip') */}
        <motion.button
          onClick={onToggleHistory}
          className="p-3 rounded-full bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors shadow-md"
          whileHover={{ scale: 1.1 }}
          whileTap={{ scale: 0.9 }}
          aria-label={historyTooltip}
          title={historyTooltip}
        >
          <History className="w-6 h-6" />
        </motion.button>
      </div> {/* Fin del Contenedor de Botones */}
    </motion.div>
  );
};

export default Header;