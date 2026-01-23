import React, { useState } from "react";
import { motion } from "framer-motion";
import {
  CheckCircle,
  XCircle,
  ChevronRight,
  Copy,
  Mail,
  Zap
} from "lucide-react";

// ==========================================================
// CORREOS POR ÁREA
// ==========================================================
const AREA_EMAILS = {
  "Producción / Operaciones": "angeloyercam09@gmail.com",
  "Mantenimiento": "mantenimiento@empresa.com",
  "Calidad (Aseguramiento y Control)": "calidad@empresa.com",
  "Logística / Cadena de Suministro": "logistica@empresa.com",
  "Almacén / Inventarios": "almacen@empresa.com",
  "Ingeniería de Procesos": "ingenieria.procesos@empresa.com",
  "Investigación y Desarrollo (I+D)": "id@empresa.com",
  "Comercial (Ventas y Marketing)": "comercial@empresa.com",
  "Compras / Adquisiciones": "compras@empresa.com",
  "Finanzas y Contabilidad": "finanzas@empresa.com",
  "Recursos Humanos (RR.HH.)": "rh@empresa.com",
  "EHS (Medio Ambiente, Seguridad y Salud)": "ehs@empresa.com",
  "Sistemas / Tecnologías de la Información (TI)": "ti@empresa.com"
};

// ==========================================================
// TEXTOS
// ==========================================================
const CARD_TEXTS = {
  es: {
    title: "TÍTULO GENERADO",
    description: "DESCRIPCIÓN INDUSTRIAL",
    benefits: "BENEFICIOS (MÉTRICAS/LEAN)",
    viable: "VIABLE",
    notViable: "NO VIABLE",
    copied: "¡Copiado!",
    copy: "Copiar",
    seeMore: "Ver más",
    seeLess: "Ver menos",
    level1: "BAJO",
    level2: "MEDIO",
    level3: "ALTO",
    sendEmail: "Enviar información por correo",
    devInfo: "INFORMACIÓN DEL DESARROLLADOR",
    devName: "Desarrollador",
    empNumber: "Número de empleado",
    area: "Área"
  }
};

const copyToClipboard = (text, callback) => {
  navigator.clipboard.writeText(text).then(callback);
};

const CopyButton = ({ content, statusKey, currentStatus, handleCopy, texts }) => {
  const isCopied = currentStatus === statusKey;
  return (
    <button
      onClick={() => handleCopy(content, statusKey)}
      className={`flex items-center text-white text-xs font-medium px-3 py-1.5 rounded-full shadow-sm 
      transition-all duration-200 ${
        isCopied ? "bg-green-500" : "bg-blue-600 hover:bg-blue-700"
      }`}
    >
      {React.createElement(isCopied ? CheckCircle : Copy, {
        className: "w-3 h-3 mr-1"
      })}
      {isCopied ? texts.copied : texts.copy}
    </button>
  );
};

// ==========================================================
// COMPONENTE PRINCIPAL
// ==========================================================
const OutputCard = ({ data, currentLang, compact = false }) => {
  const texts = CARD_TEXTS[currentLang] || CARD_TEXTS.es;

  const [copiedStatus, setCopiedStatus] = React.useState(null);
  const [expanded, setExpanded] = React.useState(false);
  const [area, setArea] = useState("");
  
  // --- NUEVO ESTADO PARA TÉRMINOS ---
  const [acceptedTerms, setAcceptedTerms] = useState(false);

  const title = data.title || "";
  const description = data.description || "";
  const benefits = data.benefits || [];

  const isViable =
    title.toUpperCase().includes("-V") &&
    !title.toUpperCase().includes("-NV");

  const StatusIcon = isViable ? CheckCircle : XCircle;
  const statusColor = isViable
    ? "bg-green-100 text-green-600 border border-green-300"
    : "bg-red-100 text-red-600 border border-red-300";
  const statusLabel = isViable ? texts.viable : texts.notViable;

  const complexity = data.complexity || { level: "Nivel 1" };
  const complexityColor = "bg-green-100 text-green-600 border border-green-300";
  const ComplexityIcon = Zap;

  const getComplexityLabel = (level) => {
    switch (level) {
      case "Nivel 1": return texts.level1;
      case "Nivel 2": return texts.level2;
      case "Nivel 3": return texts.level3;
      default: return level;
    }
  };

  const handleCopy = (content, key) => {
    setCopiedStatus(key);
    copyToClipboard(content, () =>
      setTimeout(() => setCopiedStatus(null), 2000)
    );
  };

  const benefitsString = benefits
    .map((b, i) => `${i + 1}. ${b}`)
    .join("\n");

  const handleSendToPHP = () => {
    const session = window.USER_SESSION || { nombre: "Sin Nombre", numEmpleado: "00000" };
    const complexityMap = { "Nivel 1": "B", "Nivel 2": "M", "Nivel 3": "A" };
    
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "http://localhost/vision_ai/vision_ai_home.php"; 

    const fields = {
      txtdesarrollador: session.nombre, 
      txtno_empleado: session.numEmpleado,
      cboarea: area, 
      texto_idea: data.originalIdea || "", 
      txt_titulo: title,
      txt_descripcion: description,
      txt_beneficios: benefitsString,
      txt_viabilidad: isViable ? "V" : "N",
      txt_complejidad: complexityMap[complexity.level] || "B"
    };

    Object.entries(fields).forEach(([key, value]) => {
      const input = document.createElement("input");
      input.type = "hidden";
      input.name = key;
      input.value = value;
      form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
  };

  if (compact && !expanded) {
    return (
      <motion.div
        className="bg-white shadow-md rounded-xl p-4 border border-gray-200"
        initial={{ opacity: 0, y: 15 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <div className="flex justify-between items-center">
          <h3 className="text-lg font-bold text-gray-800 mr-2">{title}</h3>
          <div className="flex flex-row items-center space-x-2">
            <div className={`flex items-center px-3 py-1 text-xs font-semibold rounded-full ${statusColor}`}>
              <StatusIcon className="w-3 h-3 mr-1" />
              {statusLabel}
            </div>
            <div className={`flex items-center px-3 py-1 text-xs font-semibold rounded-full ${complexityColor}`}>
              <ComplexityIcon className="w-3 h-3 mr-1" />
              {getComplexityLabel(complexity.level)}
            </div>
          </div>
        </div>
        <div className="flex justify-end mt-3">
          <button onClick={() => setExpanded(true)} className="text-blue-600 font-medium text-sm">
            {texts.seeMore}
          </button>
        </div>
      </motion.div>
    );
  }

  return (
    <motion.div
      className="bg-white p-6 rounded-2xl shadow-2xl space-y-6 border-t-8 border-yellow-500"
      initial={{ opacity: 0, scale: 0.95 }}
      animate={{ opacity: 1, scale: 1 }}
      transition={{ duration: 0.5 }}
    >
      {compact && (
        <button className="text-blue-600 mb-4" onClick={() => setExpanded(false)}>
          {texts.seeLess}
        </button>
      )}

      <div className="flex justify-between items-start">
        <div className="flex-1">
          <h3 className="text-sm font-medium uppercase text-gray-500">{texts.title}</h3>
          <p className="text-xl font-bold text-gray-800 mb-2">{title}</p>
          <CopyButton content={title} statusKey="title" currentStatus={copiedStatus} handleCopy={handleCopy} texts={texts} />
        </div>
        <div className="flex flex-row items-center space-x-2 ml-4">
          <div className={`flex items-center px-3 py-1 text-xs font-semibold rounded-full ${statusColor}`}>
            <StatusIcon className="w-3 h-3 mr-1" />
            {statusLabel}
          </div>
          <div className={`flex items-center px-3 py-1 text-xs font-semibold rounded-full ${complexityColor}`}>
            <ComplexityIcon className="w-3 h-3 mr-1" />
            {getComplexityLabel(complexity.level)}
          </div>
        </div>
      </div>

      <div className="border-t pt-4">
        <div className="flex justify-between items-center mb-2">
          <h3 className="text-sm font-medium uppercase text-gray-500">{texts.description}</h3>
          <CopyButton content={description} statusKey="description" currentStatus={copiedStatus} handleCopy={handleCopy} texts={texts} />
        </div>
        <p className="text-gray-700">{description}</p>
      </div>

      <div>
        <div className="flex justify-between items-center mb-2">
          <h3 className="text-sm font-medium uppercase text-gray-500">{texts.benefits}</h3>
          <CopyButton content={benefitsString} statusKey="benefits" currentStatus={copiedStatus} handleCopy={handleCopy} texts={texts} />
        </div>
        <ul className="space-y-2">
          {benefits.map((b, i) => (
            <li key={i} className="flex items-start p-3 rounded-lg bg-gray-50">
              <ChevronRight className="w-5 h-5 text-gray-500" />
              <span className="ml-2">{b}</span>
            </li>
          ))}
        </ul>
      </div>

      {isViable && (
        <>
          <div className="border-t pt-6">
            <h3 className="text-sm font-medium uppercase text-gray-500 mb-3">{texts.devInfo}</h3>
            <div className="space-y-4">
              <div>
                <label className="text-xs text-gray-600">{texts.area}</label>
                <select
                  value={area}
                  onChange={(e) => setArea(e.target.value)}
                  className="w-full mt-1 p-2 border rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none"
                >
                  <option value="">Selecciona un área de destino</option>
                  {Object.keys(AREA_EMAILS).map((a) => (
                    <option key={a} value={a}>{a}</option>
                  ))}
                </select>
              </div>
            </div>
          </div>

          {/* --- SECCIÓN DE TÉRMINOS Y CONDICIONES --- */}
          <div className="bg-gray-50 p-4 rounded-xl border border-gray-200">
            <label className="flex items-start gap-3 cursor-pointer">
              <input 
                type="checkbox" 
                checked={acceptedTerms}
                onChange={(e) => setAcceptedTerms(e.target.checked)}
                className="mt-1 w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
              />
              <span className="text-xs text-gray-600 leading-tight">
                Acepto que mi idea sea procesada por modelos de IA y confirmo que he leído los 
                <a href="http://localhost/vision_ai/terminos.php" target="_blank" className="text-blue-600 font-bold ml-1 hover:underline">
                   Términos y Condiciones Industriales.
                </a>
              </span>
            </label>
          </div>

          <div className="border-t pt-4">
            <button
              onClick={handleSendToPHP}
              disabled={!acceptedTerms || !area}
              className={`w-full flex items-center justify-center font-semibold py-3 rounded-xl shadow-md transition-all ${
                acceptedTerms && area 
                ? "bg-blue-600 hover:bg-blue-700 text-white cursor-pointer" 
                : "bg-gray-300 text-gray-500 cursor-not-allowed"
              }`}
            >
              <Mail className="w-5 h-5 mr-2" />
              Validar y Enviar a Revisión
            </button>
            
            {!acceptedTerms && (
              <p className="text-[10px] text-center text-red-400 mt-2 italic">
                * Debes aceptar los términos para habilitar el envío.
              </p>
            )}
          </div>
        </>
      )}
    </motion.div>
  );
};

export default OutputCard;