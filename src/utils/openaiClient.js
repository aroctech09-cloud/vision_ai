import OpenAI from 'openai';  

const openai = new OpenAI({  
  apiKey: process.env.REACT_APP_OPENAI_API_KEY, // Usa tu API key aquí  
  dangerouslyAllowBrowser: true // Solo para desarrollo; no uses en producción real  
});  

export default openai;