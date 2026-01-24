import React, { useState, useEffect, useCallback } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import Header from './components/Header';
import IdeaInput from './components/IdeaInput';
import ResultsSection from './components/ResultsSection';
import HistoryPanel from './components/HistoryPanel'; 
// 🔥 IMPORTAMOS LA NUEVA FUNCIÓN fetchIdeasHistory
import { generateIdeaEnhancement, fetchIdeasHistory } from './utils/aiService';

// ==========================================================
// CONSTANTES GLOBALES
// ==========================================================
const LANGUAGE_STORAGE_KEY = 'appLanguage'; // Clave para localStorage

const TEXTS = {
  es: {
    error: 'Ups, algo salió mal al generar la mejora. Verifica tu conexión o clave de API.',
    loading: 'Generando...',
    historyTooltip: 'Mostrar Historial de Ideas',
    fetchError: 'No se pudo cargar el historial de ideas.', // 🔥 NUEVO TEXTO
  },
  en: {
    error: 'Oops, something went wrong while generating the enhancement. Check your connection or API key.',
    loading: 'Generating...',
    historyTooltip: 'Show Idea History',
    fetchError: 'Could not load the idea history.', // 🔥 NUEVO TEXTO
  },
  fr: {
    error: 'Oups, une erreur est survenue lors de la génération. Vérifiez votre connexion ou clé API.',
    loading: 'Génération...',
    historyTooltip: 'Afficher l\'historique des idées',
    fetchError: 'Impossible de charger l\'historique des idées.', // 🔥 NUEVO TEXTO
  },
  pt: {
    error: 'Opa, algo deu errado ao gerar a melhoria. Verifique sua conexão ou chave de API.',
    loading: 'Gerando...',
    historyTooltip: 'Mostrar Histórico de Ideias',
    fetchError: 'Não foi possível carregar o histórico de ideias.', // 🔥 NUEVO TEXTO
  }
};
// ==========================================================

const App = () => {
  const [isLoading, setIsLoading] = useState(false);
  const [results, setResults] = useState(null);
  const [history, setHistory] = useState([]);
  const [isHistoryOpen, setIsHistoryOpen] = useState(false);
  const [showLoader, setShowLoader] = useState(true);
  
  // 🔥 1A. OBTENER EL IDIOMA DEL LOCAL STORAGE O USAR 'es' POR DEFECTO
  const [currentLang, setCurrentLang] = useState(() => {
    const storedLang = localStorage.getItem(LANGUAGE_STORAGE_KEY);
    return storedLang || 'es';
  });

  const currentText = TEXTS[currentLang] || TEXTS.es;

  // 🔥 1B. PERSISTIR EL IDIOMA CADA VEZ QUE CAMBIA
  const setLanguage = (langCode) => {
    setCurrentLang(langCode);
    localStorage.setItem(LANGUAGE_STORAGE_KEY, langCode); // Guardar en localStorage
  };

    // 🔥 2A. FUNCIÓN PARA CARGAR EL HISTORIAL DE SUPABASE
    const loadHistory = useCallback(async () => {
        try {
            const fetchedHistory = await fetchIdeasHistory();
            setHistory(fetchedHistory);
        } catch (error) {
            console.error(currentText.fetchError, error);
            // Opcional: mostrar un mensaje de error al usuario
        }
    }, [currentText.fetchError]);

    // 🔥 2B. USAR useEffect para cargar el historial al montar el componente
    useEffect(() => {
        // Ejecuta el loader por 1.5s
        const loaderTimer = setTimeout(() => setShowLoader(false), 1500);
        
        // Carga el historial de ideas de la DB
        loadHistory();
        
        return () => clearTimeout(loaderTimer);
    }, [loadHistory]); 


  // Loader component (Se mantiene igual)
  const Loader = () => (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      transition={{ duration: 0.8 }}
      className="fixed inset-0 flex items-center justify-center bg-white z-[9999]"
    >
      <motion.div
        animate={{ scale: [1, 1.2, 1], opacity: [1, 0.6, 1] }}
        transition={{ repeat: Infinity, duration: 1.2 }}
        className="text-yellow-500"
      >
        {/* ⭐ ICONO DE FOCO PREMIUM ⭐ */}
        <svg
          xmlns="http://www.w3.org/2000/svg"
          className="w-24 h-24"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="1.7"
          strokeLinecap="round"
          strokeLinejoin="round"
        >
          <path d="M9 18h6" />
          <path d="M10 22h4" />
          <path d="M12 2a7 7 0 0 0-7 7c0 2.3 1.1 4.3 2.9 5.6a3 3 0 0 1 1.1 2.3V18h6v-1.1c0-.9.4-1.7 1.1-2.3A7 7 0 0 0 19 9a7 7 0 0 0-7-7z" />
          <path d="M8 9a4 4 0 0 1 8 0" />
        </svg>
      </motion.div>
    </motion.div>
  );


  const handleGenerate = async (idea) => {
    setIsLoading(true);
    setResults(null); // Limpiar resultado anterior

    try {
      const enhancement = await generateIdeaEnhancement(idea, currentLang); 
      
      setResults(enhancement);
      // Dado que generateIdeaEnhancement ya guarda en DB, simplemente 
      // actualizamos la historia localmente y recargamos para asegurar la consistencia.
      // Opcional: Llamar a loadHistory() aquí para recargar la lista completa de la DB
      // o simplemente añadir el resultado al estado de history
      setHistory(prevHistory => [{...enhancement, created_at: new Date().toISOString()}, ...prevHistory]); 
    } catch (error) {
      alert(currentText.error);
    } finally {
      setIsLoading(false);
    }
  };

  // ------------------------------
  // MOSTRAR LOADER ANTES DE TODO
  // ------------------------------
  if (showLoader) {
    return (
      <AnimatePresence>
        <Loader />
      </AnimatePresence>
    );
  }

  return (
    <div className={`min-h-screen bg-gradient-to-br from-blue-50 to-yellow-50 py-8 transition-padding duration-300 ${isHistoryOpen ? 'pr-80' : ''}`}> 
      <div className="container mx-auto px-4 max-w-2xl">
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          transition={{ duration: 0.8 }}
        >
          <Header 
            onToggleHistory={() => setIsHistoryOpen(!isHistoryOpen)} 
            setLanguage={setLanguage}
            currentLang={currentLang}
            historyTooltip={currentText.historyTooltip}
          /> 
          
          <IdeaInput 
            onGenerate={handleGenerate} 
            isLoading={isLoading} 
            currentLang={currentLang}
            currentText={currentText}
          />
          
          <ResultsSection results={results} currentLang={currentLang} />
        </motion.div>
      </div>
      
      <HistoryPanel 
        history={history}
        isOpen={isHistoryOpen}
        onClose={() => setIsHistoryOpen(false)}
        currentLang={currentLang}
      />
      
      {isHistoryOpen && (
        <motion.div
          className="fixed inset-0 bg-black/30 z-40"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          onClick={() => setIsHistoryOpen(false)}
        />
      )}
    </div>
  );
};


export default App;