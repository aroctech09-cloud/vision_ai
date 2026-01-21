// ResultsSection.js (Modificado)
import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import OutputCard from './OutputCard';

// ==========================================================
// MODIFICACIÓN 1: Ampliar traducciones (Añadidas FR y PT)
// ==========================================================
const RESULTS_TEXTS = {
  es: {
    title: 'Análisis de Viabilidad y Mejora',
    initial: 'Tu análisis de idea aparecerá aquí.',
  },
  en: {
    title: 'Viability and Enhancement Analysis',
    initial: 'Your idea analysis will appear here.',
  },
  fr: {
    title: 'Analyse de Viabilité et d\'Amélioration',
    initial: 'Votre analyse d\'idée apparaîtra ici.',
  },
  pt: {
    title: 'Análise de Viabilidade e Melhoria',
    initial: 'Sua análise de ideia aparecerá aqui.',
  }
};
// ==========================================================

// Aceptar la prop currentLang
const ResultsSection = ({ results, currentLang }) => {
  // Selecciona el texto basado en el idioma (con fallback a 'es')
  const texts = RESULTS_TEXTS[currentLang] || RESULTS_TEXTS.es;

  return (
    <motion.div
      className="mt-8"
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      transition={{ duration: 0.8 }}
    >
      <h2 className="text-2xl font-semibold text-gray-700 mb-4 border-b pb-2">
        {texts.title} {/* <-- Traducción del título */}
      </h2>

      <AnimatePresence mode="wait">
        {results ? (
          <motion.div
            key="results"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            transition={{ duration: 0.5 }}
          >
            {/* MODIFICACIÓN 2: Pasar currentLang a OutputCard */}
            <OutputCard data={results} currentLang={currentLang} /> 
          </motion.div>
        ) : (
          <motion.div
            key="initial"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="p-6 bg-white rounded-xl shadow-lg border border-gray-100 italic text-gray-500 text-center"
          >
            {texts.initial} {/* <-- Traducción del texto inicial */}
          </motion.div>
        )}
      </AnimatePresence>
    </motion.div>
  );
};

export default ResultsSection;