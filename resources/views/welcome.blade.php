<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName }} — CRM SaaS intelligent</title>
    <meta name="description" content="{{ $appName }} centralise CRM, facturation, stock, automatisations et intégrations dans une interface moderne et exploitable.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global-font.css') }}">
</head>
<body class="welcome-page">

    <!-- ═══ HEADER (dark) ═══ -->
    <header class="site-header">
        <div class="header-inner">
            <a href="{{ url('/') }}" class="brand">
                <span class="brand-orb" aria-hidden="true">
                    <span class="orb-ring"></span>
                    <span class="orb-dot"></span>
                </span>
                <strong>{{ $appName }}</strong>
            </a>

            <nav class="site-nav">
                <a href="#modules">Modules</a>
                <a href="#workflow">Workflow</a>
                <a href="#integrations">Intégrations</a>
                <a href="#tarifs">Tarifs</a>
            </nav>

            <div class="header-cta">
                <a href="{{ route('login') }}" class="btn-outline">Connexion</a>
                <a href="{{ route('register') }}" class="btn-fill">Commencer</a>
            </div>
        </div>
    </header>

    <main>

        <!-- ═══ HERO ═══ -->
        <section class="hero">
            <!-- CSS circle animations -->
            <div class="orbs" aria-hidden="true">
                <span class="orb orb--1"></span>
                <span class="orb orb--2"></span>
                <span class="orb orb--3"></span>
                <span class="orb orb--4"></span>
                <span class="orb orb--5"></span>
                <span class="orb orb--6"></span>
            </div>

            <div class="hero-inner">
                <div class="hero-copy" data-reveal>
                    <span class="pill">CRM nouvelle génération</span>
                    <h1>
                        Votre pipeline,<br>
                        <span class="hero-word" id="rotating-word">vos clients.</span>
                    </h1>
                    <p class="hero-sub">
                        {{ $appName }} réunit CRM, facturation, stock et automatisations dans un seul espace — fluide, lisible, et taillé pour votre équipe.
                    </p>
                    <div class="hero-btns">
                        <a href="{{ route('register') }}" class="btn-fill btn-lg">Créer mon espace</a>
                        <a href="#modules" class="btn-line btn-lg">Voir les modules</a>
                    </div>
                    <ul class="proof-list">
                        <li>CRM, facturation, stock et projets</li>
                        <li>Google, Slack, Notion, Trello inclus</li>
                        <li>Sauvegardes cloud et exports automatiques</li>
                    </ul>
                </div>

                <div class="hero-visual" data-reveal>
                    <div class="dash-mock">
                        <div class="dash-header">
                            <span class="dash-dot"></span><span class="dash-dot"></span><span class="dash-dot"></span>
                            <span class="dash-label">{{ $appName }} · Dashboard</span>
                        </div>
                        <div class="dash-body">
                            <!-- Simulated metric cards -->
                            <div class="dash-metrics">
                                <div class="metric-card">
                                    <span class="metric-label">Chiffre d'affaires</span>
                                    <span class="metric-value" data-count="128400" data-suffix=" DT">0</span>
                                    <span class="metric-delta up">+14%</span>
                                </div>
                                <div class="metric-card">
                                    <span class="metric-label">Clients actifs</span>
                                    <span class="metric-value" data-count="342" data-suffix="">0</span>
                                    <span class="metric-delta up">+28</span>
                                </div>
                                <div class="metric-card">
                                    <span class="metric-label">Taux de conversion</span>
                                    <span class="metric-value" data-count="68.4" data-suffix="%">0</span>
                                    <span class="metric-delta up">+3.1</span>
                                </div>
                            </div>
                            <!-- Simulated pipeline bar -->
                            <div class="pipeline-block">
                                <span class="pipeline-label">Pipeline commercial</span>
                                <div class="pipeline-bar">
                                    <span class="pb pb--blue" style="width:38%"><em>Prospects</em></span>
                                    <span class="pb pb--indigo" style="width:26%"><em>Qualifiés</em></span>
                                    <span class="pb pb--teal" style="width:19%"><em>Propositions</em></span>
                                    <span class="pb pb--green" style="width:17%"><em>Signés</em></span>
                                </div>
                            </div>
                            <!-- Simulated activity feed -->
                            <div class="feed-block">
                                <span class="pipeline-label">Activité récente</span>
                                <ul class="feed">
                                    <li><span class="feed-dot dot--blue"></span><span>Nouvelle opportunité — <strong>Agence Nova</strong></span><time>il y a 3 min</time></li>
                                    <li><span class="feed-dot dot--green"></span><span>Facture envoyée — <strong>TechPro SARL</strong></span><time>il y a 11 min</time></li>
                                    <li><span class="feed-dot dot--amber"></span><span>Devis signé — <strong>BioLab Inc.</strong></span><time>il y a 38 min</time></li>
                                    <li><span class="feed-dot dot--teal"></span><span>Stock mis à jour — <strong>Ref. AX-402</strong></span><time>il y a 1h</time></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ STATS ═══ -->
        <section class="stats-row" data-reveal>
            @foreach($stats as $stat)
            <div class="stat-item">
                <strong data-count="{{ $stat['value'] }}" data-suffix="{{ $stat['suffix'] }}">0</strong>
                <span>{{ $stat['label'] }}</span>
            </div>
            @endforeach
        </section>

        <!-- ═══ MODULES ═══ -->
        <section class="section-light" id="modules">
            <div class="container">
                <div class="section-tag" data-reveal>
                    <span class="tag-pill">Modules</span>
                    <h2>Tout ce dont votre équipe a besoin,<br>dans un seul tableau de bord.</h2>
                    <p>Chaque module est pensé pour coopérer. Pas des outils séparés — un système qui grandit avec vous.</p>
                </div>

                <div class="modules-grid">
                    @foreach($pillars as $pillar)
                    <article class="module-card" data-reveal>
                        <div class="module-accent module-accent--{{ $pillar['tone'] }}">
                            <span class="module-circle"></span>
                        </div>
                        <div class="module-body">
                            <span class="module-kicker">{{ $pillar['eyebrow'] }}</span>
                            <h3>{{ $pillar['title'] }}</h3>
                            <p>{{ $pillar['body'] }}</p>
                            <ul class="module-points">
                                @foreach($pillar['points'] as $point)
                                <li>{{ $point }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </article>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- ═══ WORKFLOW ═══ -->
        <section class="section-light section-sep" id="workflow">
            <div class="container">
                <div class="section-tag narrow" data-reveal>
                    <span class="tag-pill">Workflow</span>
                    <h2>De la capture à la décision —<br>un flux continu.</h2>
                </div>

                <div class="workflow-track">
                    @foreach($workflowSteps as $i => $step)
                    <div class="wf-step" data-reveal>
                        <div class="wf-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                        <div class="wf-content">
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ $step['body'] }}</p>
                        </div>
                        <div class="wf-orbs" aria-hidden="true">
                            <span class="wf-orb wf-orb--a"></span>
                            <span class="wf-orb wf-orb--b"></span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- ═══ INTEGRATIONS ═══ -->
        <section class="section-light section-sep" id="integrations">
            <div class="container">
                <div class="section-tag" data-reveal>
                    <span class="tag-pill">Intégrations</span>
                    <h2>Connecté à vos outils dès le premier jour.</h2>
                    <p>{{ $appName }} s'intègre aux services que vous utilisez déjà — sans configuration complexe.</p>
                </div>

                <div class="integrations-area" data-reveal>
                    @foreach($extensionCategories as $cat)
                    <div class="int-group">
                        <span class="int-cat">{{ $cat['label'] }}</span>
                        <div class="int-chips">
                            @foreach($cat['items'] as $item)
                            <span class="int-chip">{{ $item }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- ═══ PRICING ═══ -->
        <section class="section-light section-sep" id="tarifs">
            <div class="container">
                <div class="section-tag narrow" data-reveal>
                    <span class="tag-pill">Tarifs</span>
                    <h2>Un abonnement. Tous les modules.<br>Quatre rythmes.</h2>
                    <p>Pas de module payant en extra. Tout est inclus, quel que soit l'engagement choisi.</p>
                </div>

                <div class="pricing-wrap" data-reveal>
                    <div class="pricing-head">
                        <h3>{{ $appName }} — Accès complet</h3>
                        <span class="pricing-allin">Tout inclus</span>
                    </div>

                    <div class="pricing-periods">
                        @foreach($pricingPeriods as $period)
                        <div class="period-card{{ $period['recommended'] ? ' period-card--featured' : '' }}">
                            <div class="period-top">
                                <strong>{{ $period['label'] }}</strong>
                                @if($period['badge'])
                                <span class="period-badge">{{ $period['badge'] }}</span>
                                @endif
                            </div>
                            <div class="period-price">{{ $period['total_label'] }}</div>
                            <p>{{ $period['monthly_label'] }} / mois</p>
                            @if($period['discount'] > 0)
                            <small>Économie vs mensuel : {{ $period['discount'] }}%</small>
                            @else
                            <small>Sans engagement.</small>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    <div class="pricing-features">
                        <div class="pf-item">CRM clients et suivi commercial</div>
                        <div class="pf-item">Devis, factures et exports PDF</div>
                        <div class="pf-item">Stock, fournisseurs et bons de livraison</div>
                        <div class="pf-item">Projets, automatisations et alertes</div>
                        <div class="pf-item">Google, Dropbox, Slack, Notion, Trello</div>
                        <div class="pf-item">Sauvegardes cloud et historique complet</div>
                    </div>

                    <div class="pricing-actions">
                        <a href="{{ route('register') }}" class="btn-fill btn-lg">Choisir mon abonnement</a>
                        <a href="{{ route('login') }}" class="btn-line btn-lg">J'ai déjà un espace</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ SECURITY / RELIABILITY ═══ -->
        <section class="section-light section-sep" id="securite">
            <div class="container">
                <div class="section-tag" data-reveal>
                    <span class="tag-pill">Fiabilité</span>
                    <h2>Sécurisé, sauvegardé,<br>toujours disponible.</h2>
                </div>
                <div class="reliability-grid">
                    @foreach($highlights as $h)
                    <div class="rely-card" data-reveal>
                        <div class="rely-circle" aria-hidden="true"></div>
                        <h3>{{ $h['title'] }}</h3>
                        <p>{{ $h['body'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- ═══ CTA ═══ -->
        <section class="cta-band" data-reveal>
            <div class="cta-orbs" aria-hidden="true">
                <span class="cta-orb cta-orb--1"></span>
                <span class="cta-orb cta-orb--2"></span>
                <span class="cta-orb cta-orb--3"></span>
            </div>
            <div class="cta-inner">
                <span class="tag-pill tag-pill--light">Commencer maintenant</span>
                <h2>Votre CRM opérationnel<br>en moins de 5 minutes.</h2>
                <p>Créez votre espace, invitez votre équipe et connectez vos outils — dès aujourd'hui.</p>
                <div class="cta-btns">
                    <a href="{{ route('register') }}" class="btn-fill btn-lg">Créer un compte gratuit</a>
                    <a href="{{ route('login') }}" class="btn-outline-light btn-lg">Accéder à mon espace</a>
                </div>
            </div>
        </section>

    </main>

    <!-- ═══ FOOTER (dark) ═══ -->
    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <a href="{{ url('/') }}" class="brand brand--light">
                    <span class="brand-orb" aria-hidden="true">
                        <span class="orb-ring"></span>
                        <span class="orb-dot"></span>
                    </span>
                    <strong>{{ $appName }}</strong>
                </a>
                <p>Une plateforme CRM pour centraliser relation client, documents, opérations et services connectés dans un seul espace.</p>
            </div>

            <nav class="footer-nav">
                <h4>Plateforme</h4>
                <a href="#modules">Modules</a>
                <a href="#workflow">Workflow</a>
                <a href="#integrations">Intégrations</a>
                <a href="#tarifs">Tarifs</a>
            </nav>

            <nav class="footer-nav">
                <h4>Accès</h4>
                <a href="{{ route('login') }}">Se connecter</a>
                <a href="{{ route('register') }}">Créer un compte</a>
                <a href="{{ route('password.request') }}">Mot de passe oublié</a>
            </nav>

            <div class="footer-integrations">
                <h4>Intégrations actives</h4>
                <div class="footer-chips">
                    @foreach(collect($heroApps)->take(8) as $app)
                    <span class="footer-chip">{{ $app['name'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="footer-bar">
            <span>© {{ date('Y') }} {{ $appName }}</span>
            <span>{{ count($extensionCategories) }} univers d'intégrations</span>
        </div>
    </footer>

    <script src="{{ asset('js/welcome.js') }}"></script>
</body>
</html>