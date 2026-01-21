import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Loader2, Zap, Mic, MicOff, User } from 'lucide-react';

const INPUT_TEXTS = {
  es: {
    placeholder: 'Describe tu idea para mejorar un proceso o producto BIC (ej: "Usar plástico reciclado en el cuerpo del lapicero").',
    button: 'Generar Mejora',
    userLabel: 'COLABORADOR REGISTRANDO:'
  },
  en: {
    placeholder: 'Describe your idea for improving a BIC process or product (e.g., "Use recycled plastic in the pen barrel").',
    button: 'Generate Enhancement',
    userLabel: 'COLLABORATOR REGISTERING:'
  },
  fr: {
    placeholder: 'Décrivez votre idée para améliorer un processus ou un produit BIC (ex: "Utiliser du plastique reciclado dans le corps du stylo").',
    button: 'Générer l\'Amélioration',
    userLabel: 'COLLABORATEUR INSCRIT:'
  },
  pt: {
    placeholder: 'Descreva sua ideia para melhorar um processo ou produto BIC (ex: "Usar plástico reciclado no corpo da caneta").',
    button: 'Gerar Melhoria',
    userLabel: 'COLABORADOR REGISTRANDO:'
  }
};

const VOICE_TEXTS = {
  es: { listening: 'Escuchando...', stopped: 'Reconocimiento detenido.', notSupported: 'El reconocimiento de voz no es compatible.' },
  en: { listening: 'Listening...', stopped: 'Recognition stopped.', notSupported: 'Voice recognition is not supported.' },
  fr: { listening: 'Écoute...', stopped: 'Reconnaissance arrêtée.', notSupported: 'La reconnaissance vocale n\'est pas prise en charge.' },
  pt: { listening: 'Ouvindo...', stopped: 'Reconhecimento parado.', notSupported: 'O reconocimiento de voz não é suportado.' }
};

const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

const IdeaInput = ({ onGenerate, isLoading, currentLang, currentText }) => {
  const [idea, setIdea] = useState('');
  const [isListening, setIsListening] = useState(false);
  const [recognition, setRecognition] = useState(null);

  // --- ESTADO PARA EL COLABORADOR ---
  const [colaborador, setColaborador] = useState({
    nombre: "Sin Nombre",
    id: "00000"
  });

  const texts = INPUT_TEXTS[currentLang] || INPUT_TEXTS.es;
  const voiceTexts = VOICE_TEXTS[currentLang] || VOICE_TEXTS.es;

  useEffect(() => {
    // 1. INTENTAR LEER DE LA URL
    const params = new URLSearchParams(window.location.search);
    const nombreUrl = params.get('user');
    const idUrl = params.get('id');

    if (nombreUrl && idUrl) {
      const decodedName = decodeURIComponent(nombreUrl);
      
      // Asignación correcta: Nombre al nombre, ID al id
      setColaborador({
        nombre: decodedName,
        id: idUrl
      });
      
      // Guardamos en LocalStorage para persistencia
      localStorage.setItem('user_name', decodedName);
      localStorage.setItem('user_id', idUrl);

      // Limpiamos la URL para que se vea limpia (opcional)
      window.history.replaceState({}, document.title, window.location.pathname);
      
    } else {
      // 2. SI NO HAY URL, LEER DE LOCALSTORAGE
      const nombreLS = localStorage.getItem('user_name');
      const idLS = localStorage.getItem('user_id');
      if (nombreLS && idLS) {
        setColaborador({ nombre: nombreLS, id: idLS });
      }
    }

    // 3. CONFIGURACIÓN DE VOZ
    if (SpeechRecognition) {
      const recognizer = new SpeechRecognition();
      recognizer.continuous = false;
      let langCode = 'es-ES';
      switch (currentLang) {
        case 'en': langCode = 'en-US'; break;
        case 'fr': langCode = 'fr-FR'; break;
        case 'pt': langCode = 'pt-BR'; break;
        default: langCode = 'es-ES';
      }
      recognizer.lang = langCode;

      recognizer.onresult = (event) => {
        const transcript = event.results[0][0].transcript;
        setIdea(prevIdea => prevIdea + (prevIdea.length > 0 ? ' ' : '') + transcript);
        setIsListening(false);
      };
      recognizer.onend = () => setIsListening(false);
      setRecognition(recognizer);
    }
  }, [currentLang]);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (idea.trim()) {
      onGenerate(idea);
      setIdea('');
    }
  };

  const handleVoiceInput = () => {
    if (!recognition) return alert(voiceTexts.notSupported);
    if (isListening) {
      recognition.stop();
    } else {
      recognition.start();
      setIsListening(true);
    }
  };

  return (
    <motion.form
      onSubmit={handleSubmit}
      className="bg-white p-6 rounded-2xl shadow-xl space-y-4"
      initial={{ scale: 0.9, opacity: 0 }}
      animate={{ scale: 1, opacity: 1 }}
    >
      {/* RECUADRO DEL COLABORADOR */}
      <div className="bg-slate-50 border border-slate-200 p-4 rounded-xl text-left">
        <span className="text-[10px] font-extrabold text-slate-500 block mb-1 uppercase tracking-wider">
          {texts.userLabel}
        </span>
        <div className="flex items-center gap-2">
          <User className="w-5 h-5 text-blue-600" />
          <span className="font-bold text-slate-800 text-sm">{colaborador.nombre}</span>
          <span className="text-slate-400 text-xs font-mono ml-2">ID: {colaborador.id}</span>
        </div>
      </div>

      {/* ÁREA DE TEXTO */}
      <div className="relative">
        <textarea
          className={`w-full p-4 pr-12 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition-all resize-none min-h-[120px] text-gray-800 ${isListening ? 'border-red-500 ring-red-500' : ''}`}
          placeholder={isListening ? voiceTexts.listening : texts.placeholder}
          value={idea}
          onChange={(e) => setIdea(e.target.value)}
          disabled={isLoading || isListening}
        />
        <button
          type="button"
          onClick={handleVoiceInput}
          className={`absolute top-1/2 right-3 -translate-y-1/2 p-2 rounded-full ${isListening ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-600'}`}
        >
          {isListening ? <MicOff className="w-5 h-5" /> : <Mic className="w-5 h-5" />}
        </button>
      </div>

      <motion.button
        type="submit"
        disabled={isLoading || idea.trim().length === 0 || isListening}
        className={`w-full py-3 rounded-xl font-semibold text-lg flex items-center justify-center transition-all shadow-lg ${
          isLoading || idea.trim().length === 0 || isListening
            ? 'bg-gray-400 text-gray-200'
            : 'bg-gradient-to-r from-blue-600 to-yellow-500 text-white'
        }`}
      >
        {isLoading ? (
          <><Loader2 className="w-5 h-5 mr-2 animate-spin" /> {currentText.loading}</>
        ) : (
          <><Zap className="w-5 h-5 mr-2" /> {texts.button}</>
        )}
      </motion.button>
    </motion.form>
  );
};

export default IdeaInput;