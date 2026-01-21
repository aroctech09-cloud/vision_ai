// HistoryPanel.js (Modificado con botón Descargar + Export correcto)
import React from 'react';
import { motion } from 'framer-motion';
import { X, Clock, Download } from 'lucide-react';
import OutputCard from './OutputCard';

// Traducciones
const HISTORY_TEXTS = {
  es: { title: 'Historial de Ideas', empty: 'Aún no has generado ninguna idea.' },
  en: { title: 'Idea History', empty: 'You haven\'t generated any ideas yet.' },
  fr: { title: 'Historique des Idées', empty: 'Vous n\'avez pas encore généré d\'idées.' },
  pt: { title: 'Histórico de Ideias', empty: 'Você ainda não gerou nenhuma ideia.' }
};

// ======================================================
// ✔ FUNCIÓN CORREGIDA
//    Exporta solo Título, Idea y Beneficios
//    + Corrige acentos en Excel (BOM UTF-8)
//    + Soporte para item.idea o item.description
// ======================================================
const exportToExcel = (history) => {
  if (history.length === 0) return;

  let content = "Título,Idea,Beneficios\n";

  history.forEach((item) => {
    const title = item.title || "";
    const ideaRaw = item.idea || item.description || "";

    const idea = ideaRaw
      ?.replace(/\n/g, " ")
      .replace(/,/g, ";")
      || "";

    const benefits = Array.isArray(item.benefits)
      ? item.benefits.join(" | ").replace(/,/g, ";")
      : (item.benefits || "").replace(/,/g, ";");

    content += `${title},${idea},${benefits}\n`;
  });

  // ✔ Agregar BOM para evitar caracteres raros en Excel
  const BOM = "\uFEFF";
  const blob = new Blob([BOM + content], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);

  const link = document.createElement('a');
  link.href = url;
  link.download = "historial_ideas.csv";
  link.click();

  URL.revokeObjectURL(url);
};

const HistoryPanel = ({ history, isOpen, onClose, currentLang }) => {
  const texts = HISTORY_TEXTS[currentLang] || HISTORY_TEXTS.es;

  const panelVariants = {
    open: { x: 0, transition: { type: 'spring', stiffness: 100, damping: 20 } },
    closed: { x: '100%', transition: { type: 'spring', stiffness: 100, damping: 20 } }
  };

  return (
    <motion.div
      className="fixed top-0 right-0 w-80 h-full bg-gray-50 z-50 shadow-2xl overflow-y-auto"
      initial="closed"
      animate={isOpen ? 'open' : 'closed'}
      variants={panelVariants}
    >
      {/* ======= HEADER ======= */}
      <div className="p-4 border-b bg-white sticky top-0 z-10 flex items-center justify-between shadow-md">

        {/* TÍTULO + ÍCONO DESCARGA */}
        <div className="flex items-center space-x-2">
          <Clock className="w-5 h-5 text-blue-600" />
          <h2 className="text-xl font-bold text-gray-800">{texts.title}</h2>

          {/* BOTON DESCARGAR */}
          <motion.button
            onClick={() => exportToExcel(history)}
            className="p-1 rounded-md bg-green-100 text-green-700 hover:bg-green-200 transition flex items-center"
            whileHover={{ scale: 1.1 }}
            whileTap={{ scale: 0.9 }}
            title="Descargar historial"
          >
            <Download className="w-5 h-5" />
          </motion.button>
        </div>

        {/* BOTÓN CERRAR */}
        <motion.button
          onClick={onClose}
          className="p-1 rounded-full text-gray-500 hover:text-gray-800 transition-colors"
          whileHover={{ scale: 1.1 }}
          whileTap={{ scale: 0.9 }}
        >
          <X className="w-6 h-6" />
        </motion.button>
      </div>

      {/* ======= CONTENIDO ======= */}
      <div className="p-4 space-y-4">
        {history.length === 0 ? (
          <p className="text-gray-500 italic text-center py-8">
            {texts.empty}
          </p>
        ) : (
          [...history].reverse().map((item, index) => (
            <motion.div
              key={index}
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, delay: index * 0.05 }}
            >
              <OutputCard 
                data={item}
                currentLang={currentLang}
                compact={true}
              />
            </motion.div>
          ))
        )}
      </div>
    </motion.div>
  );
};

export default HistoryPanel;
