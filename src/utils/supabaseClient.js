import { createClient } from '@supabase/supabase-js';

// Cargar las variables de entorno
const supabaseUrl = process.env.REACT_APP_SUPABASE_URL;
const supabaseAnonKey = process.env.REACT_APP_SUPABASE_ANON_KEY;

// Inicializar el cliente
if (!supabaseUrl || !supabaseAnonKey) {
  console.error("Faltan las variables de entorno de Supabase. Verifica tu archivo .env");
}

export const supabase = createClient(supabaseUrl, supabaseAnonKey);