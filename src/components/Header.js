import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { History, Globe, ChevronDown, Check } from 'lucide-react';

const LANGUAGES = [
  { code: 'es', display: 'ES', name: 'Español', tooltip: 'Cambiar a Español' },
  { code: 'en', display: 'EN', name: 'English', tooltip: 'Switch to English' },
  { code: 'fr', display: 'FR', name: 'Français', tooltip: 'Passer au Français' },
  { code: 'pt', display: 'PT', name: 'Português', tooltip: 'Mudar para Português' },
];

const Header = ({ onToggleHistory, currentLang, setLanguage, historyTooltip }) => {
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  
  const currentLangInfo = LANGUAGES.find(lang => lang.code === currentLang) || LANGUAGES[0];
  const langTooltip = `${currentLangInfo.name} (Current: ${currentLangInfo.display})`; 

  const handleLanguageChange = (code) => {
      setLanguage(code);
      setIsDropdownOpen(false);
  }

  return (
    <motion.div
      className="bg-white shadow-lg rounded-2xl p-6 mb-8 flex items-center justify-between"
      initial={{ opacity: 0, y: -20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.6 }}
    >
      {/* SECCIÓN DEL LOGO Y TÍTULO */}
      <div className="flex items-center space-x-3">
        {/* Logo imponente */}
        <motion.img 
          src="/Logo_Vision.png" // Asegúrate de que el nombre coincida con tu archivo en 'public'
          alt="Vision AI Logo"
          className="w-12 h-12 object-contain" // Tamaño ajustable
          whileHover={{ rotate: 5, scale: 1.1 }}
        />
        <h1 className="text-4xl font-bold bg-gradient-to-r from-blue-600 to-yellow-500 bg-clip-text text-transparent">
          Vision AI 
        </h1>
      </div>
      
      {/* Contenedor para agrupar los botones */}
      <div className="flex space-x-4"> 
        {/* Selector de Idioma */}
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
                <ChevronDown className={`w-4 h-4 ml-1 transition-transform ${isDropdownOpen ? 'rotate-180' : 'rotate-0'}`} />
            </motion.button>

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

        {/* Botón Historial */}
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
      </div>
    </motion.div>
  );
};

export default Header;