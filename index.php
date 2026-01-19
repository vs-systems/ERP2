<?php
/**
 * Vecino Seguro - Corporate Landing Page
 * Ultra-Premium Aesthetic
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vecino Seguro | Soluciones Tecnológicas de Alta Seguridad</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
        }

        .hero-gradient {
            background: radial-gradient(circle at 50% -20%, #1e293b 0%, #020617 100%);
        }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .accent-gradient {
            background: linear-gradient(135deg, #136dec 0%, #3b82f6 100%);
        }

        .text-gradient {
            background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        .float-anim {
            animation: float 6s ease-in-out infinite;
        }
    </style>
</head>

<body class="bg-[#020617] text-white antialiased">

    <!-- Header -->
    <nav class="fixed top-0 w-full z-50 px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center glass rounded-2xl px-8 py-3">
            <div class="flex items-center gap-2">
                <img src="https://vecinoseguro.com.ar/Logos/VSLogo.png" alt="VS" class="h-10">
                <span class="font-bold text-lg tracking-tighter">VECINO<span class="text-blue-500">SEGURO</span></span>
            </div>
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-400">
                <a href="#servicios" class="hover:text-white transition-colors">Servicios</a>
                <a href="#tecnologia" class="hover:text-white transition-colors">Tecnología</a>
                <a href="#contacto" class="hover:text-white transition-colors">Contacto</a>
                <a href="catalogo_publico.php" class="hover:text-white transition-colors">Catálogo</a>
            </div>
            <a href="login.php"
                class="accent-gradient px-6 py-2.5 rounded-full text-sm font-bold shadow-lg shadow-blue-500/20 hover:scale-105 transition-all">
                ERP ACCESO
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="min-h-screen flex flex-col justify-center items-center relative pt-20 px-6 hero-gradient">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 left-1/4 w-[500px] h-[500px] bg-blue-500/10 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-1/4 right-1/4 w-[400px] h-[400px] bg-purple-500/10 rounded-full blur-[100px]">
            </div>
        </div>

        <div class="max-w-4xl text-center z-10 space-y-8">
            <span
                class="inline-block px-4 py-1.5 rounded-full border border-blue-500/30 bg-blue-500/5 text-blue-400 text-xs font-bold tracking-widest uppercase mb-4">
                Innovación en Seguridad Electrónica
            </span>
            <h1 class="text-6xl md:text-8xl font-extrabold tracking-tight text-gradient leading-[1.1]">
                Tecnología que <br> <span class="text-blue-500">Protege</span> tu Mundo.
            </h1>
            <p class="text-lg md:text-xl text-slate-400 max-w-2xl mx-auto leading-relaxed">
                Expertos en soluciones integrales de seguridad electrónica, automatización y monitoreo inteligente para
                consorcios y empresas argentinas.
            </p>
            <div class="flex flex-col md:flex-row gap-4 justify-center pt-4">
                <a href="catalogo_publico.php"
                    class="bg-white text-black px-10 py-4 rounded-full font-bold hover:bg-slate-200 transition-all flex items-center justify-center gap-2">
                    VER CATÁLOGO ONLINE
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
                <a href="login.php" class="glass px-10 py-4 rounded-full font-bold hover:bg-white/5 transition-all">
                    GESTIÓN INTERNA
                </a>
            </div>
        </div>

        <!-- Float Image -->
        <div class="mt-20 max-w-5xl w-full px-6 float-anim">
            <div class="glass p-4 rounded-3xl overflow-hidden shadow-2xl">
                <img src="https://vecinoseguro.com.ar/Logos/VS_Mockup.png" alt="Dashboard Preview"
                    class="w-full rounded-2xl border border-white/5"
                    onerror="this.src='https://placehold.co/1200x600/0f172a/white?text=VS+Enterprise+Platform'">
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="servicios" class="py-32 px-6 bg-[#020617]">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-end gap-6 mb-16">
                <div class="max-w-xl">
                    <h2 class="text-4xl md:text-5xl font-bold mb-6">Soluciones360°.</h2>
                    <p class="text-slate-400">Protección multinivel diseñada para los desafíos de hoy.</p>
                </div>
                <div class="h-[1px] flex-1 bg-white/5 mx-8 mb-4 hidden md:block"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="glass p-10 rounded-[32px] hover:border-blue-500/30 transition-all group">
                    <div
                        class="w-14 h-14 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-6 text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-all">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Video Vigilancia</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Sistemas IP de alta definición con análisis
                        inteligente de video y detección de intrusos en tiempo real.</p>
                </div>

                <div class="glass p-10 rounded-[32px] hover:border-blue-500/30 transition-all group">
                    <div
                        class="w-14 h-14 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-6 text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-all">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Control de Acceso</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Gestión biométrica y RFID para edificios y
                        empresas con reportes detallados y administración remota.</p>
                </div>

                <div class="glass p-10 rounded-[32px] hover:border-blue-500/30 transition-all group">
                    <div
                        class="w-14 h-14 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-6 text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-all">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Automatización</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Integración de sistemas inteligentes para el
                        control eficiente de iluminación, portones y perímetros.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-20 px-6 border-t border-white/5">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-10">
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <img src="https://vecinoseguro.com.ar/Logos/VSLogo.png" alt="VS" class="h-8">
                    <span class="font-bold text-lg tracking-tighter uppercase">Vecino Seguro</span>
                </div>
                <p class="text-slate-500 text-sm">© 2026 Vecino Seguro S.A. Todos los derechos reservados.</p>
            </div>
            <div class="flex gap-8 text-slate-400 text-sm">
                <a href="#" class="hover:text-white transition-colors">Instagram</a>
                <a href="#" class="hover:text-white transition-colors">WhatsApp</a>
                <a href="login.php" class="text-blue-500 font-bold">Portal Cliente</a>
            </div>
        </div>
    </footer>

</body>

</html>