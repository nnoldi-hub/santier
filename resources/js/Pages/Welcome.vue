<template>
    <Head title="MODULIA - Management de santier" />

    <div class="landing min-h-screen overflow-x-hidden text-slate-800">
        <div class="noise-layer"></div>

        <header class="max-w-6xl mx-auto px-4 sm:px-6 pt-6 relative z-10">
            <div class="bg-[#1E1E1E]/95 border border-slate-700 rounded-2xl px-4 sm:px-8 py-4 flex items-center justify-between shadow-xl">
                <div class="header-logo">
                    <div class="logo-crop logo-crop--header">
                        <img src="/brand/logo_modulia.png" alt="Modulia" class="logo-crop__img" />
                    </div>
                </div>
                <div class="flex items-center gap-1.5 sm:gap-3 flex-shrink-0">
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="px-2.5 py-1.5 sm:px-4 sm:py-2 rounded-lg border border-slate-500 text-xs sm:text-sm font-medium text-slate-100 hover:bg-slate-800 transition whitespace-nowrap"
                    >
                        Login
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="px-2.5 py-1.5 sm:px-4 sm:py-2 rounded-lg bg-[var(--brand-orange)] text-white text-xs sm:text-sm font-semibold hover:brightness-95 transition whitespace-nowrap"
                    >
                        Trial {{ trialDays }} zile
                    </Link>
                </div>
            </div>
        </header>

        <main class="relative z-10">
            <section class="max-w-6xl mx-auto px-4 sm:px-6 pt-10 sm:pt-16">
                <div class="grid lg:grid-cols-2 gap-10 items-center">
                    <div>
                        <div class="hero-logo mb-6">
                            <div class="logo-crop logo-crop--hero">
                                <img src="/brand/logo_modulia.png" alt="Modulia" class="logo-crop__img" />
                            </div>
                        </div>

                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/70 border border-slate-200 text-xs font-semibold text-slate-600 mb-5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Lansare pilot activa
                        </div>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black leading-tight text-slate-900">
                            Management de santier
                            <span class="text-[var(--brand-orange)]">simplu, modern</span>
                            si masurabil.
                        </h1>

                        <p class="mt-5 text-lg text-slate-600 max-w-xl">
                            Cu Modulia, proiectele tale devin clare, ordonate si usor de controlat.
                        </p>

                        <div class="mt-7 flex flex-wrap gap-3">
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                @click="trackCtaClick('hero', 'incearca_modulia')"
                                class="px-6 py-3 rounded-xl bg-[var(--brand-orange)] text-white font-semibold shadow-lg shadow-orange-500/20 hover:-translate-y-0.5 transition"
                            >
                                Incearca Modulia
                            </Link>
                            <a href="#solicita-demo" @click="trackCtaClick('hero', 'solicita_demo')" class="px-6 py-3 rounded-xl bg-white border border-slate-300 font-semibold hover:bg-slate-50 transition">Solicita demo</a>
                            <a href="#video-demo" @click="trackCtaClick('hero', 'vezi_video_30_sec')" class="px-6 py-3 rounded-xl border border-slate-300 font-semibold text-slate-700 hover:bg-slate-50 transition">Vezi video 30 sec</a>
                        </div>

                        <div class="mt-8 grid grid-cols-3 gap-3 max-w-md">
                            <div class="bg-white/80 border border-slate-200 rounded-xl p-3">
                                <div class="text-xl font-black text-slate-900">+35%</div>
                                <div class="text-xs text-slate-500">taskuri inchise la timp</div>
                            </div>
                            <div class="bg-white/80 border border-slate-200 rounded-xl p-3">
                                <div class="text-xl font-black text-slate-900">-22%</div>
                                <div class="text-xs text-slate-500">rework pe defecte</div>
                            </div>
                            <div class="bg-white/80 border border-slate-200 rounded-xl p-3">
                                <div class="text-xl font-black text-slate-900">1 click</div>
                                <div class="text-xs text-slate-500">raport managerial</div>
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="absolute -inset-4 bg-gradient-to-br from-orange-300/20 via-blue-300/20 to-cyan-200/20 blur-2xl rounded-3xl"></div>
                        <div class="relative rounded-3xl border border-slate-200 bg-white/85 backdrop-blur p-5 sm:p-6 shadow-xl">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-slate-500">Demo live</div>
                                    <div class="font-semibold">Modulia Control Panel</div>
                                </div>
                                <div class="text-xs px-2 py-1 rounded-md bg-emerald-100 text-emerald-700">Online</div>
                            </div>

                            <div class="space-y-3">
                                <div class="rounded-xl border border-slate-200 p-3 flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-semibold">Renovare cladire birouri</div>
                                        <div class="text-xs text-slate-500">Deadline: 18 iulie</div>
                                    </div>
                                    <div class="text-xs px-2 py-1 rounded-md bg-amber-100 text-amber-700">In progress</div>
                                </div>
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="flex items-center justify-between text-xs text-slate-500 mb-2">
                                        <span>Progres etapa finisaje</span>
                                        <span>72%</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full w-[72%] bg-gradient-to-r from-[var(--brand-orange)] to-[var(--brand-blue)]"></div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-xl border border-slate-200 p-3">
                                        <div class="text-xs text-slate-500">Defecte deschise</div>
                                        <div class="text-2xl font-black">12</div>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 p-3">
                                        <div class="text-xs text-slate-500">Cost vs buget</div>
                                        <div class="text-2xl font-black">96%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="video-demo" class="max-w-6xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20">
                <div class="grid lg:grid-cols-[1.1fr_0.9fr] gap-6 items-stretch">
                    <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm">
                        <div class="aspect-video overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                            <iframe
                                v-if="landingVideo.type === 'youtube'"
                                class="h-full w-full"
                                :src="landingVideo.src"
                                title="Modulia demo rapid"
                                loading="lazy"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allowfullscreen
                            ></iframe>
                            <video v-else-if="landingVideo.type === 'file'" class="h-full w-full" controls preload="metadata">
                                <source :src="landingVideo.src" />
                            </video>
                            <div v-else class="h-full w-full flex items-center justify-center text-sm text-slate-500">
                                Video in pregatire. Revenim curand cu prezentarea completa.
                            </div>
                        </div>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-orange-50 to-blue-50 p-6 sm:p-8">
                        <span class="text-xs uppercase tracking-wide font-semibold text-slate-500">Hero video</span>
                        <h2 class="mt-2 text-2xl sm:text-3xl font-black text-slate-900">Vezi in 20-30 sec cum arata controlul santierului</h2>
                        <p class="mt-3 text-slate-600">Clipul arata fluxul complet: proiect, taskuri, defecte, raport PDF si dashboard de management.</p>
                        <ul class="mt-5 space-y-2 text-sm text-slate-600">
                            <li>• claritate pe status si responsabilitati</li>
                            <li>• urmarire defecte cu poze si termene</li>
                            <li>• raportare rapida pentru management</li>
                        </ul>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="#solicita-demo" @click="trackCtaClick('video_section', 'programeaza_demo_live')" class="px-5 py-3 rounded-xl bg-[var(--brand-blue)] text-white font-semibold hover:brightness-95 transition">Programeaza demo live</a>
                            <a :href="`mailto:${salesEmail || supportEmail || 'contact@modulia.ro'}?subject=Solicitare%20brosura%20Modulia`" @click="trackCtaClick('video_section', 'solicita_brosura_pdf')" class="px-5 py-3 rounded-xl border border-slate-300 bg-white font-semibold hover:bg-slate-50 transition">Solicita brosura PDF</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="max-w-6xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Cum functioneaza Modulia</h2>
                <p class="mt-2 text-slate-600">Treci de la haos operational la control in 3 pasi simpli.</p>
                <div class="mt-6 grid md:grid-cols-3 gap-4">
                    <article v-for="step in howItWorks" :key="step.title" class="rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pasul {{ step.step }}</div>
                        <h3 class="mt-2 text-lg font-bold text-slate-900">{{ step.title }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ step.text }}</p>
                    </article>
                </div>

                <!-- Mockup ilustrativ (nu e screenshot real - inlocuieste cu o captura reala cand exista) -->
                <div class="mt-4 rounded-3xl border border-slate-200 bg-white/85 backdrop-blur p-5 sm:p-6 shadow-sm">
                    <div class="grid sm:grid-cols-3 gap-3">
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">1. Configurare</div>
                            <div class="mt-2 space-y-1.5">
                                <div class="h-2 w-3/4 rounded-full bg-slate-100"></div>
                                <div class="h-2 w-1/2 rounded-full bg-slate-100"></div>
                            </div>
                            <div class="mt-2 inline-flex text-[10px] px-2 py-0.5 rounded-md bg-orange-100 text-orange-700">Sablon etape</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">2. Executie</div>
                            <div class="mt-2 space-y-1.5">
                                <div class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span><div class="h-2 w-2/3 rounded-full bg-slate-100"></div></div>
                                <div class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span><div class="h-2 w-1/2 rounded-full bg-slate-100"></div></div>
                            </div>
                            <div class="mt-2 inline-flex text-[10px] px-2 py-0.5 rounded-md bg-blue-100 text-blue-700">Taskuri &amp; defecte</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">3. Raportare</div>
                            <div class="mt-2 flex items-center justify-between text-xs">
                                <span class="text-slate-500">Export PDF/XLSX</span>
                                <span class="font-black text-slate-900">1 click</span>
                            </div>
                            <div class="mt-2 inline-flex text-[10px] px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-700">Gata pentru management</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="max-w-6xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Pentru cine este Modulia</h2>
                <p class="mt-2 text-slate-600">Fiecare rol vede ce are nevoie pentru decizii rapide si executie buna.</p>
                <div class="mt-6 grid md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <article v-for="segment in audiences" :key="segment.title" class="rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="text-slate-500"><Icon :icon="segment.icon" size="h-7 w-7" /></div>
                        <h3 class="mt-3 font-bold text-slate-900">{{ segment.title }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ segment.text }}</p>
                    </article>
                </div>
            </section>

            <section class="max-w-6xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Probleme reale pe care le rezolvi din prima saptamana</h2>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">
                        <Icon :icon="CheckCircleIcon" size="h-3.5 w-3.5" /> Rezolvat in prima saptamana
                    </span>
                </div>
                <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="item in painPoints" :key="item.title" class="bg-white border border-slate-200 rounded-2xl p-5">
                        <div class="text-slate-500"><Icon :icon="item.icon" size="h-7 w-7" /></div>
                        <div class="mt-3 font-semibold">{{ item.title }}</div>
                        <p class="mt-1 text-sm text-slate-600">{{ item.text }}</p>
                    </div>
                </div>
            </section>

            <section id="module-esentiale" class="max-w-6xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Module esentiale MODULIA</h2>
                <p class="mt-2 text-slate-600">Ofertare inteligenta, WBS, taskuri, documente, financiar, AI si control enterprise intr-o singura platforma.</p>

                <!-- Mockup ilustrativ (nu e screenshot real - inlocuieste cu o captura reala cand exista) -->
                <div class="mt-6 rounded-3xl border border-slate-200 bg-white/85 backdrop-blur p-5 sm:p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs uppercase tracking-wide text-slate-400 font-semibold">Platforma in actiune</span>
                        <span class="text-xs px-2 py-1 rounded-md bg-emerald-100 text-emerald-700">Live</span>
                    </div>
                    <div class="grid sm:grid-cols-3 gap-3">
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">WBS - Structura fundatie</div>
                            <div class="mt-2 h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full w-[64%] bg-gradient-to-r from-[var(--brand-orange)] to-[var(--brand-blue)]"></div>
                            </div>
                            <div class="mt-1 text-[11px] text-slate-400">64% complet</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">Financiar proiect</div>
                            <div class="mt-1 text-lg font-black text-slate-900">96%</div>
                            <div class="text-[11px] text-slate-400">din bugetul alocat</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-xs text-slate-500">AI Tools</div>
                            <div class="mt-2 text-[11px] text-slate-600">"Buget etapa finisaje aproape de limita - verifica achizitiile recente."</div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div v-for="feature in features" :key="feature.title" class="feature-card rounded-2xl p-5 text-white">
                        <div class="text-white/90"><Icon :icon="feature.icon" size="h-7 w-7" /></div>
                        <div class="mt-3 font-semibold text-lg">{{ feature.title }}</div>
                        <p class="mt-1 text-sm text-slate-100/90">{{ feature.text }}</p>
                    </div>
                </div>
            </section>

            <section class="max-w-6xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900">De ce Modulia</h2>
                <div class="mt-6 grid lg:grid-cols-[1fr_0.85fr] gap-4 items-stretch">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <article v-for="reason in whyModulia" :key="reason.title" class="rounded-2xl border border-slate-200 bg-white p-5">
                            <h3 class="font-bold text-slate-900">{{ reason.title }}</h3>
                            <p class="mt-2 text-sm text-slate-600">{{ reason.text }}</p>
                        </article>
                    </div>

                    <!-- Mockup ilustrativ AI dashboard (nu e screenshot real - inlocuieste cu o captura reala cand exista) -->
                    <div class="rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-orange-50 to-blue-50 p-5 sm:p-6 shadow-sm">
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <Icon :icon="CpuChipIcon" size="h-4 w-4 text-[var(--brand-orange)]" /> AI Dashboard
                        </div>
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3">
                            <div class="text-xs text-amber-700 font-semibold">Indicator AI</div>
                            <div class="mt-1 text-sm font-bold text-amber-900">Risc intarziere: 18%</div>
                        </div>
                        <div class="mt-3 rounded-xl border border-slate-200 bg-white p-3">
                            <div class="text-xs text-slate-500 font-semibold">Sugestie AI</div>
                            <div class="mt-1 text-sm text-slate-700">Etapa "Instalatii" are 2 taskuri blocate - realoca echipa pentru a evita intarzierea livrarii.</div>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                <div class="text-[11px] text-slate-500">Oferte generate AI</div>
                                <div class="text-lg font-black text-slate-900">128</div>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                <div class="text-[11px] text-slate-500">Timp economisit</div>
                                <div class="text-lg font-black text-slate-900">~6h/sapt</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 rounded-2xl border border-orange-200 bg-orange-50 p-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-slate-900">Brosura comerciala Modulia</h3>
                        <p class="text-sm text-slate-600">Trimite solicitarea si primesti PDF-ul de prezentare cu pachete, onboarding si beneficii.</p>
                    </div>
                    <a :href="`mailto:${salesEmail || supportEmail || 'contact@modulia.ro'}?subject=Solicitare%20brosura%20comerciala%20Modulia`" @click="trackCtaClick('why_modulia', 'solicita_brosura')" class="px-5 py-3 rounded-xl bg-slate-900 text-white font-semibold hover:bg-slate-800 transition">Solicita brosura</a>
                </div>
            </section>

            <section class="max-w-6xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Ce spun clientii pilot</h2>
                <div class="mt-6 grid md:grid-cols-3 gap-4">
                    <article v-for="(quote, index) in testimonials" :key="quote.name" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-1 text-amber-400">
                            <svg v-for="star in 5" :key="star" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.8L10 14.9l-5.2 2.62.99-5.8-4.21-4.1 5.82-.85z" /></svg>
                        </div>
                        <p class="mt-3 text-sm text-slate-700">"{{ quote.text }}"</p>
                        <div class="mt-4 flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full text-xs font-bold text-white" :class="avatarColor(index)">
                                {{ initials(quote.name) }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-900">{{ quote.name }}</div>
                                <div class="text-xs text-slate-500">{{ quote.role }}</div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section id="preturi" class="max-w-6xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Preturi clare pentru fiecare etapa</h2>
                <div class="mt-6 grid md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div v-for="plan in plans" :key="plan.key" class="rounded-2xl border p-5 bg-white" :class="plan.highlight ? 'border-[var(--brand-orange)] shadow-lg shadow-orange-300/20' : 'border-slate-200'">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-lg">{{ plan.name }}</h3>
                            <span class="text-xs px-2 py-1 rounded" :class="plan.highlight ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-600'">{{ plan.badge }}</span>
                        </div>
                        <div class="mt-2 text-3xl font-black text-slate-900">{{ formatPrice(plan.price) }}</div>
                        <div class="text-xs text-slate-500">{{ plan.period }}</div>
                        <ul class="mt-4 space-y-2 text-sm text-slate-600">
                            <li v-for="item in plan.items" :key="item">• {{ item }}</li>
                        </ul>
                        <div class="mt-5">
                            <a
                                v-if="plan.key === 'enterprise'"
                                href="#solicita-demo"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition"
                            >
                                {{ plan.cta_label }}
                            </a>
                            <Link
                                v-else-if="canRegister"
                                :href="route('register')"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition"
                            >
                                {{ plan.cta_label }}
                            </Link>
                        </div>
                    </div>
                </div>
                <p class="mt-5 text-center text-xs text-slate-500">Fara contract, fara instalare, fara costuri ascunse.</p>
            </section>

            <section v-if="socialLinksToRender.length" class="max-w-6xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20">
                <div class="rounded-3xl border border-slate-200 bg-gradient-to-r from-slate-900 via-slate-800 to-[var(--brand-blue)] p-8 sm:p-10 text-white overflow-hidden relative">
                    <div class="absolute -right-16 -top-16 h-48 w-48 rounded-full bg-white/10 blur-2xl"></div>
                    <div class="absolute -left-12 bottom-0 h-40 w-40 rounded-full bg-[var(--brand-orange)]/20 blur-2xl"></div>
                    <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-2xl">
                            <span class="text-xs uppercase tracking-[0.3em] text-white/70 font-semibold">Social & Comunitate</span>
                            <h2 class="mt-3 text-2xl sm:text-3xl font-black">Urmareste Modulia pe canalele publice</h2>
                            <p class="mt-3 text-sm sm:text-base text-white/80">
                                Publicam demo-uri, update-uri de produs, exemple de folosire si continut util pentru firmele din constructii si renovari.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 w-full lg:w-auto">
                            <a
                                v-for="link in socialLinksToRender"
                                :key="`social-section-${link.label}`"
                                :href="link.url"
                                target="_blank"
                                rel="noopener"
                                @click="trackCtaClick('social_section', `open_${link.key}`)"
                                class="group rounded-2xl border border-white/15 bg-white/10 px-4 py-4 backdrop-blur hover:bg-white/15 hover:-translate-y-0.5 transition"
                            >
                                <div class="text-xs uppercase tracking-[0.2em] text-white/60">{{ link.shortLabel }}</div>
                                <div class="mt-2 text-base font-bold">{{ link.label }}</div>
                                <div class="mt-1 text-sm text-white/70 group-hover:text-white/90">Deschide canalul</div>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section id="solicita-demo" class="max-w-6xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20 pb-20">
                <div class="grid lg:grid-cols-[1.15fr_0.85fr] gap-6 items-stretch">
                    <div class="rounded-3xl border border-slate-200 bg-gradient-to-r from-white via-orange-50 to-blue-50 p-8 sm:p-10">
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Vrei sa vezi daca se potriveste firmei tale?</h2>
                    <p class="mt-2 text-slate-600 max-w-2xl">Pornesti in 10 minute cu wizard-ul de onboarding, un proiect demo si raport exportabil pentru management.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="px-6 py-3 rounded-xl bg-[var(--brand-orange)] text-white font-semibold hover:brightness-95 transition"
                        >
                            Creeaza cont trial
                        </Link>
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="px-6 py-3 rounded-xl border border-slate-300 bg-white font-semibold hover:bg-slate-50 transition"
                        >
                            Intra in platforma
                        </Link>
                    </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-8 sm:p-10 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-xs uppercase tracking-wide text-slate-500">Solicita demo</div>
                                <h3 class="mt-2 text-xl font-black text-slate-900">Trimite datele si te contactam</h3>
                            </div>
                            <div class="w-11 h-11 rounded-2xl bg-[var(--brand-orange)]/10 flex items-center justify-center text-[var(--brand-orange)]"><Icon :icon="EnvelopeIcon" size="h-5 w-5" /></div>
                        </div>

                        <form class="mt-6 space-y-3" @submit.prevent="submitDemoRequest">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Companie</label>
                                <input v-model="demoForm.company_name" type="text" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="Nume companie" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Persoana de contact</label>
                                <input v-model="demoForm.contact_name" type="text" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="Nume si prenume" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label>
                                <input v-model="demoForm.contact_email" type="email" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="email@firma.ro" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Telefon</label>
                                <input v-model="demoForm.contact_phone" type="text" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="07xx xxx xxx" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Numar utilizatori estimat</label>
                                <input v-model="demoForm.estimated_users" type="number" min="1" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="Ex: 25" required />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Tip personalizare dorita</label>
                                <select v-model="demoForm.customization_scope" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" required>
                                    <option value="" disabled>Selecteaza o optiune</option>
                                    <option value="branding">Branding documente</option>
                                    <option value="template">Template documente</option>
                                    <option value="approvals">Flux aprobari</option>
                                    <option value="white_label">White-label</option>
                                    <option value="custom_domain">Domeniu propriu</option>
                                    <option value="full_enterprise">Pachet enterprise complet</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Ce vrei sa vezi?</label>
                                <textarea v-model="demoForm.notes" rows="3" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="Ex: 5 proiecte, 2 echipe, raportare si calendar"></textarea>
                            </div>
                            <button :disabled="demoForm.processing" class="w-full rounded-xl bg-[var(--brand-blue)] px-4 py-3 text-white font-semibold hover:brightness-95 disabled:opacity-60 transition">
                                {{ demoForm.processing ? 'Se trimite...' : 'Trimite solicitarea' }}
                            </button>
                            <p v-if="demoForm.recentlySuccessful" class="text-sm font-medium text-emerald-700">Solicitarea a fost trimisa. Revenim cu un raspuns.</p>
                        </form>
                    </div>
                </div>
            </section>
        </main>

        <footer class="relative z-10 border-t border-slate-200 bg-white/70">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-4 text-sm text-slate-600">
                <div>
                    <div class="flex items-center gap-2">
                        <img src="/brand/logo_modulia.png" alt="Modulia" class="h-6 w-6 object-contain" />
                        <span class="font-semibold text-slate-800">Modulia</span>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Șantierul devine clar.</p>
                    <p class="mt-4 text-xs text-slate-400">© 2026 Modulia</p>
                </div>

                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Produs</div>
                    <ul class="mt-3 space-y-2">
                        <li><a href="#solicita-demo" class="hover:text-[var(--brand-blue)] hover:underline">Solicita demo</a></li>
                        <li><a href="#module-esentiale" class="hover:text-[var(--brand-blue)] hover:underline">Module esentiale</a></li>
                        <li><a href="#preturi" class="hover:text-[var(--brand-blue)] hover:underline">Preturi</a></li>
                    </ul>
                </div>

                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Legal</div>
                    <ul class="mt-3 space-y-2">
                        <li><Link :href="route('legal.terms')" class="hover:text-[var(--brand-blue)] hover:underline">Termeni &amp; Conditii</Link></li>
                        <li><Link :href="route('legal.privacy')" class="hover:text-[var(--brand-blue)] hover:underline">Confidentialitate (GDPR)</Link></li>
                    </ul>
                </div>

                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Contact</div>
                    <ul class="mt-3 space-y-2">
                        <li><a :href="`mailto:${supportEmail || 'suport@modulia.ro'}`" class="hover:text-[var(--brand-blue)] hover:underline">{{ supportEmail || 'suport@modulia.ro' }}</a></li>
                        <li><a href="https://modulia.ro" target="_blank" rel="noopener" class="hover:text-[var(--brand-blue)] hover:underline">modulia.ro</a></li>
                    </ul>
                    <div v-if="socialLinksToRender.length" class="mt-4 flex items-center gap-2">
                        <a v-for="link in socialLinksToRender" :key="link.label" :href="link.url" target="_blank" rel="noopener" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)] transition" :aria-label="link.label">
                            {{ link.shortLabel }}
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import {
    PuzzlePieceIcon,
    ClockIcon,
    MagnifyingGlassIcon,
    WrenchScrewdriverIcon,
    FolderIcon,
    ArrowUpTrayIcon,
    LightBulbIcon,
    Squares2X2Icon,
    CheckCircleIcon,
    CalendarDaysIcon,
    BanknotesIcon,
    CpuChipIcon,
    ShieldCheckIcon,
    UserGroupIcon,
    BuildingOffice2Icon,
    ChartBarIcon,
    BriefcaseIcon,
    EnvelopeIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    appName: { type: String, default: 'Modulia' },
    companyName: { type: String, default: 'Modulia' },
    trialDays: { type: Number, default: 14 },
    supportEmail: { type: String, default: '' },
    salesEmail: { type: String, default: '' },
    landingVideoUrl: { type: String, default: '' },
    socialLinks: { type: Object, default: () => ({}) },
    plans: { type: Array, default: () => [] },
    canLogin: Boolean,
    canRegister: Boolean,
});

const DEFAULT_LANDING_VIDEO_URL = '';

const painPoints = [
    { icon: PuzzlePieceIcon, title: 'Taskuri pierdute', text: 'Ai un backlog clar pe proiect, responsabil si deadline, fara mesaje imprastiate.' },
    { icon: ClockIcon, title: 'Etape intarziate', text: 'Monitorizezi progresul zilnic si intervii inainte sa intarzie santierul.' },
    { icon: MagnifyingGlassIcon, title: 'Lipsa vizibilitate', text: 'Dashboard unificat pentru status, cost, defecte si decizii operative.' },
    { icon: WrenchScrewdriverIcon, title: 'Defecte deschise', text: 'Snag list cu prioritati, asignare si due date pana la rezolvare.' },
    { icon: FolderIcon, title: 'Oferte dispersate', text: 'Versionare devize si istoric centralizat, usor de comparat.' },
    { icon: ArrowUpTrayIcon, title: 'Rapoarte lente', text: 'Export XLSX/PDF managerial cu filtre avansate in cateva secunde.' },
];

const features = [
    { icon: LightBulbIcon, title: 'Ofertare inteligenta', text: 'Generezi oferte in cateva minute, cu sabloane inteligente, costuri reale si timeline automat. Modulia invata din ofertele tale si devine tot mai precisa.' },
    { icon: Squares2X2Icon, title: 'Proiecte & WBS', text: 'Structura clara pe etape, taskuri si progres. Totul modular, exact ca brandul Modulia.' },
    { icon: CheckCircleIcon, title: 'Taskuri & Progres', text: 'Vezi ce se intampla pe santier in timp real. Actualizari, poze, rapoarte, responsabilitati - totul intr-un singur loc.' },
    { icon: CalendarDaysIcon, title: 'Calendar & Planificare', text: 'Planificare vizuala, clara, fara haos. Stii mereu cine, ce si cand lucreaza.' },
    { icon: FolderIcon, title: 'Documente', text: 'Documente ordonate, aprobari, versiuni, exporturi. Totul masurabil si usor de gasit.' },
    { icon: BanknotesIcon, title: 'Financiar & Cost Tracking', text: 'Costuri reale, materiale, manopera, profit, devize. Totul transparent si masurabil.' },
    { icon: CpuChipIcon, title: 'AI Tools', text: 'AI care te ajuta sa generezi oferte, sa analizezi proiecte si sa vezi riscurile din timp. Modern, rapid, inteligent.' },
    { icon: ShieldCheckIcon, title: 'Roluri & Permisiuni Enterprise', text: 'Control complet asupra accesului. Superadmin, Admin firma, roluri custom, permisiuni granulare.' },
];

const howItWorks = [
    { step: 1, title: 'Configurezi proiectul in 10 minute', text: 'Pornesti cu sabloane de etape, responsabili si termene. Echipa vede imediat ce are de facut.' },
    { step: 2, title: 'Executi si urmaresti progresul zilnic', text: 'Taskuri, defecte si costuri intr-un singur tablou de bord, fara fisiere imprastiate.' },
    { step: 3, title: 'Raportezi clar catre management', text: 'Exporti PDF/XLSX, verifici riscuri si iei decizii pe date reale, nu pe presupuneri.' },
];

const audiences = [
    { icon: UserGroupIcon, title: 'Antreprenori generali', text: 'Coordonare centralizata pe mai multe santiere, echipe si subcontractori.' },
    { icon: BuildingOffice2Icon, title: 'Firme de renovari', text: 'Planificare simpla pe etape scurte, control pe defecte si livrare la termen.' },
    { icon: ChartBarIcon, title: 'Manageri de proiect', text: 'Vizibilitate operationala completa: status, blocaje, costuri si decizii rapide.' },
    { icon: BriefcaseIcon, title: 'Proprietari / management', text: 'Rapoarte executive, indicatori de performanta si control asupra bugetelor.' },
];

const whyModulia = [
    { title: 'Construit pentru santier, nu generic', text: 'Fluxurile sunt gandite pentru realitatea din teren: etape, defecte, validari si inchideri.' },
    { title: 'Implementare rapida, fara proiect IT mare', text: 'Intri pe trial, configurezi echipa si ai primele rezultate in cateva zile, nu luni.' },
    { title: 'Date unificate pentru decizii', text: 'Taskuri, costuri si rapoarte sunt intr-un singur sistem, fara dublari si versiuni conflictuale.' },
    { title: 'Scalabil de la pilot la enterprise', text: 'Incepi simplu si extinzi cu roluri, aprobari, branding si automatizari pe masura ce cresti.' },
];

const testimonials = [
    { name: 'Andrei Pop', role: 'Director Operatiuni, firma renovari', text: 'In primele 2 saptamani am redus timpul de urmarire a taskurilor cu aproape 30%.' },
    { name: 'Mihai Ionescu', role: 'Project Manager, antreprenor general', text: 'E prima data cand avem status clar pe defecte si termene, fara apeluri continue.' },
    { name: 'Ioana Matei', role: 'Administrator, companie constructii', text: 'Rapoartele catre management sunt gata in minute. Inainte pierdeam ore in Excel.' },
];

const landingVideo = computed(() => resolveLandingVideo(props.landingVideoUrl || DEFAULT_LANDING_VIDEO_URL));
const socialLinksToRender = computed(() => buildSocialLinks(props.socialLinks || {}));

const AVATAR_COLORS = ['bg-[var(--brand-orange)]', 'bg-[var(--brand-blue)]', 'bg-slate-700'];

function avatarColor(index) {
    return AVATAR_COLORS[index % AVATAR_COLORS.length];
}

function initials(name) {
    return String(name || '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

function formatPrice(price) {
    const numericPrice = Number(price || 0);

    if (numericPrice === 0) {
        return '0 lei';
    }

    return new Intl.NumberFormat('ro-RO', {
        style: 'currency',
        currency: 'RON',
        maximumFractionDigits: 0,
    }).format(numericPrice);
}

const demoForm = useForm({
    company_name: '',
    segment: 'renovari',
    contact_name: '',
    contact_email: '',
    contact_phone: '',
    estimated_users: '',
    customization_scope: '',
    notes: '',
});

function submitDemoRequest() {
    trackEvent('demo_form_submit_attempt', {
        placement: 'landing_welcome',
        has_company_name: Boolean(demoForm.company_name),
        estimated_users: Number(demoForm.estimated_users || 0),
        customization_scope: demoForm.customization_scope || '',
    });

    demoForm.post(route('demo-request.store'), {
        preserveScroll: true,
        onSuccess: () => {
            trackEvent('demo_form_submit_success', {
                placement: 'landing_welcome',
            });
            demoForm.reset('contact_name', 'contact_email', 'contact_phone', 'estimated_users', 'customization_scope', 'notes');
        },
        onError: () => {
            trackEvent('demo_form_submit_error', {
                placement: 'landing_welcome',
            });
        },
    });
}

function trackCtaClick(section, cta) {
    trackEvent('landing_cta_click', {
        section,
        cta,
    });
}

function trackEvent(eventName, payload = {}) {
    if (typeof window === 'undefined') {
        return;
    }

    const data = {
        event: eventName,
        page: 'welcome',
        ...payload,
    };

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(data);

    if (typeof window.gtag === 'function') {
        window.gtag('event', eventName, payload);
    }
}

function normalizeVideoEmbedUrl(rawUrl) {
    const value = String(rawUrl || '').trim();

    if (!value) {
        return '';
    }

    if (value.includes('youtube-nocookie.com/embed/') || value.includes('youtube.com/embed/')) {
        return value;
    }

    try {
        const url = new URL(value);
        const host = url.hostname.replace('www.', '');

        if (host === 'youtu.be') {
            const videoId = url.pathname.split('/').filter(Boolean)[0];

            if (videoId) {
                return `https://www.youtube-nocookie.com/embed/${videoId}?rel=0`;
            }
        }

        if (host === 'youtube.com' || host === 'm.youtube.com') {
            const videoId = url.searchParams.get('v');

            if (videoId) {
                return `https://www.youtube-nocookie.com/embed/${videoId}?rel=0`;
            }
        }
    } catch (error) {
        return '';
    }

    return '';
}

function isDirectVideoUrl(rawUrl) {
    const value = String(rawUrl || '').trim();

    if (!value) {
        return false;
    }

    try {
        const url = new URL(value);
        const pathname = String(url.pathname || '').toLowerCase();

        return /\.(mp4|webm|ogg)(\?.*)?$/.test(pathname);
    } catch (error) {
        return /^\/(storage\/)?[^\s]+\.(mp4|webm|ogg)(\?.*)?$/i.test(value);
    }
}

function resolveLandingVideo(rawUrl) {
    const youtubeEmbedUrl = normalizeVideoEmbedUrl(rawUrl);

    if (youtubeEmbedUrl) {
        return {
            type: 'youtube',
            src: youtubeEmbedUrl,
        };
    }

    if (isDirectVideoUrl(rawUrl)) {
        return {
            type: 'file',
            src: String(rawUrl || '').trim(),
        };
    }

    return {
        type: 'none',
        src: '',
    };
}

function buildSocialLinks(links) {
    const items = [
        { key: 'facebook', label: 'Facebook', shortLabel: 'f' },
        { key: 'instagram', label: 'Instagram', shortLabel: 'ig' },
        { key: 'linkedin', label: 'LinkedIn', shortLabel: 'in' },
        { key: 'tiktok', label: 'TikTok', shortLabel: 'tt' },
        { key: 'youtube', label: 'YouTube', shortLabel: 'yt' },
    ];

    return items
        .map((item) => ({
            ...item,
            url: String(links?.[item.key] || '').trim(),
        }))
        .filter((item) => item.url);
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&family=Poppins:wght@500;600;700&display=swap');

.landing {
    --brand-orange: #FF7A00;
    --brand-blue: #0057FF;
    --brand-dark: #2E2E2E;
    --brand-light: #F2F2F2;
    font-family: 'Inter', sans-serif;
    background:
        radial-gradient(80rem 40rem at 95% -10%, rgba(0, 87, 255, 0.18), transparent 65%),
        radial-gradient(70rem 35rem at -5% 5%, rgba(255, 122, 0, 0.2), transparent 60%),
        linear-gradient(180deg, #f8fafc 0%, var(--brand-light) 100%);
}

.noise-layer {
    position: fixed;
    inset: 0;
    pointer-events: none;
    opacity: 0.05;
    background-image: radial-gradient(#1f2937 0.5px, transparent 0.5px);
    background-size: 5px 5px;
}

h1,
h2,
h3 {
    font-family: 'Manrope', sans-serif;
}

.feature-card {
    background: linear-gradient(140deg, #2E2E2E 0%, #0057FF 55%, #FF7A00 100%);
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.header-logo {
    display: flex;
    align-items: center;
}

.logo-crop {
    overflow: hidden;
    border-radius: 14px;
}

.logo-crop__img {
    height: 100%;
    width: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
}

.logo-crop--header {
    height: 72px;
    width: 420px;
}

.logo-crop--hero {
    height: 160px;
    width: 620px;
}

@media (max-width: 640px) {
    .logo-crop--header {
        height: clamp(36px, 10vw, 64px);
        width: clamp(120px, 34vw, 300px);
    }

    .logo-crop--hero {
        height: clamp(64px, 18vw, 130px);
        width: clamp(210px, 62vw, 420px);
    }
}
</style>
