<script setup lang="ts">
import { ref } from 'vue';

// Key features for hero section
const keyFeatures = [
    { icon: 'pi-users', text: 'Pequenos grupos (máx. 8-10 alunos)' },
    { icon: 'pi-user', text: 'Aprendizagem personalizada' },
    { icon: 'pi-sun', text: 'Currículo integrado à natureza' }
];

// Advantages data
const advantages = [
    {
        icon: 'pi-lightbulb',
        title: 'Aprendizagem Baseada em Projetos',
        description: 'Utilizando elementos costeiros para exploração científica e criatividade.',
        iconColor: 'text-cyan-600',
        bgColor: 'bg-cyan-50'
    },
    {
        icon: 'pi-calendar',
        title: 'Flexibilidade Curricular',
        description: 'Adaptado ao estilo de vida praiano, respeitando marés e condições climáticas.',
        iconColor: 'text-blue-600',
        bgColor: 'bg-blue-50'
    },
    {
        icon: 'pi-users',
        title: 'Turmas Reduzidas',
        description: 'Máximo de 8-10 estudantes por tutor para atenção individualizada.',
        iconColor: 'text-teal-600',
        bgColor: 'bg-teal-50'
    },
    {
        icon: 'pi-globe',
        title: 'Ciências Marinhas',
        description: 'Integração de ecossistemas costeiros no currículo educacional.',
        iconColor: 'text-emerald-600',
        bgColor: 'bg-emerald-50'
    },
    {
        icon: 'pi-sun',
        title: 'Educação Ambiental',
        description: 'Consciência ecológica como componente transversal do aprendizado.',
        iconColor: 'text-amber-600',
        bgColor: 'bg-amber-50'
    },
    {
        icon: 'pi-building',
        title: 'Estrutura Híbrida',
        description: 'Espaços físicos confortáveis com atividades ao ar livre regulares.',
        iconColor: 'text-sky-600',
        bgColor: 'bg-sky-50'
    }
];

// Learning spaces
const learningSpaces = [
    {
        icon: 'pi-home',
        title: 'Salas Amplas',
        description: 'Ambientes climatizados com vista para o oceano e equipamentos modernos.',
        iconColor: 'text-blue-700'
    },
    {
        icon: 'pi-sun',
        title: 'Laboratório Natural',
        description: 'A praia como extensão da sala de aula para aprendizagem prática.',
        iconColor: 'text-orange-600'
    },
    {
        icon: 'pi-list',
        title: 'Horta Marinha',
        description: 'Jardim sustentável e compostagem para educação ambiental prática.',
        iconColor: 'text-green-600'
    },
    {
        icon: 'pi-book',
        title: 'Biblioteca Oceânica',
        description: 'Acervo temático com foco em ciências marinhas e sustentabilidade.',
        iconColor: 'text-cyan-600'
    }
];

// Educational programs
const educationalPrograms = [
    {
        level: 'Educação Infantil',
        age: '3-5 anos',
        description: 'Descoberta sensorial na natureza e primeiros contatos com o ambiente marinho através de brincadeiras e exploração.',
        color: 'bg-blue-600'
    },
    {
        level: 'Fundamental I',
        age: '6-10 anos',
        description: 'Alfabetização e matemática contextualizadas, projetos com ecossistemas costeiros e iniciação científica.',
        color: 'bg-cyan-600'
    },
    {
        level: 'Fundamental II',
        age: '11-14 anos',
        description: 'Investigação científica avançada, projetos de impacto ambiental local e desenvolvimento de pensamento crítico.',
        color: 'bg-teal-600'
    },
    {
        level: 'Ensino Médio',
        age: '15-17 anos',
        description: 'Preparação para ENEM/vestibular com foco em sustentabilidade, projetos de vida integrados ao contexto local.',
        color: 'bg-emerald-600'
    }
];

// Enrollment steps
const enrollmentSteps = [
    {
        number: 1,
        title: 'Contato Inicial',
        description: 'Entre em contato conosco para conhecer nossa metodologia. Agendaremos uma conversa para entender as necessidades da sua família.'
    },
    {
        number: 2,
        title: 'Visita Personalizada',
        description: 'Conheça nossos espaços e a equipe. Participe de uma manhã de vivência para entender nosso dia a dia educacional.'
    },
    {
        number: 3,
        title: 'Avaliação Diagnóstica',
        description: 'Processo amigável para conhecer seu filho, identificar interesses e necessidades de aprendizagem individuais.'
    },
    {
        number: 4,
        title: 'Matrícula',
        description: 'Finalização do processo e período de adaptação. Bem-vindo à nossa comunidade de aprendizagem!'
    }
];

// Special activities
const specialActivities = [
    {
        icon: 'pi-compass',
        title: 'Programa "Maré do Conhecimento"',
        description: 'Saídas de campo regulares para exploração de ecossistemas costeiros e aprendizagem vivencial.',
        color: 'text-blue-600'
    },
    {
        icon: 'pi-heart',
        title: 'Projetos de Conservação',
        description: 'Limpeza de praias, monitoramento de tartarugas marinhas e ações de proteção ambiental.',
        color: 'text-green-600'
    },
    {
        icon: 'pi-star',
        title: 'Oficinas Temáticas',
        description: 'Astronomia na praia, culinária com plantas nativas, fotografia da natureza e arte sustentável.',
        color: 'text-purple-600'
    },
    {
        icon: 'pi-globe',
        title: 'Intercâmbio Cultural',
        description: 'Parcerias com escolas em outras regiões costeiras para troca de experiências e projetos colaborativos.',
        color: 'text-orange-600'
    }
];

// Blog posts
const blogPosts = [
    {
        date: '05',
        month: 'FEVEREIRO',
        title: 'Aprendizagem na Natureza: Benefícios do Homeschooling na Praia',
        excerpt: 'Descubra como o ambiente costeiro potencializa o desenvolvimento cognitivo e emocional das crianças, proporcionando experiências únicas de aprendizado...',
        image: '/demo/images/school/1.jpg'
    },
    {
        date: '28',
        month: 'JANEIRO',
        title: 'Projeto Tartarugas: Nossos Alunos na Conservação Marinha',
        excerpt: 'Estudantes participam do monitoramento de ninhos de tartarugas marinhas, aplicando conhecimentos de biologia e matemática em ações práticas de preservação...',
        image: '/demo/images/school/2.jpg'
    },
    {
        date: '15',
        month: 'JANEIRO',
        title: 'Como Funciona o Homeschooling: Guia para Famílias',
        excerpt: 'Entenda a legislação brasileira, nosso papel como escola parceira e como organizamos o processo educacional respeitando o ritmo de cada criança...',
        image: '/demo/images/school/5.jpg'
    }
];

// Statistics
const statistics = [
    { icon: 'pi-users', count: '8-10', label: 'Alunos por Tutor' },
    { icon: 'pi-sun', count: '3x', label: 'Atividades Semanais na Praia' },
    { icon: 'pi-shield', count: '100%', label: 'Educadores Qualificados' },
    { icon: 'pi-heart', count: '15+', label: 'Projetos Ambientais/Ano' }
];

// Team members
const teamMembers = [
    {
        name: 'Prof. Maria Oliveira',
        role: 'Bióloga Marinha e Pedagoga',
        specialty: 'Ciências Marinhas',
        image: '/demo/images/school/prof/1.jpg'
    },
    {
        name: 'Prof. João Santos',
        role: 'Educador Físico',
        specialty: 'Esportes Aquáticos',
        image: '/demo/images/school/prof/2.jpg'
    },
    {
        name: 'Prof. Ana Silva',
        role: 'Psicopedagoga',
        specialty: 'Desenvolvimento Infantil',
        image: '/demo/images/school/prof/3.jpg'
    }
];
</script>

<template>
    <div class="min-h-screen bg-white">
        <!-- Header/Navigation -->
        <header class="bg-gradient-to-r from-blue-900 via-blue-800 to-cyan-700 text-white">
            <div class="container mx-auto px-6 py-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-12">
                        <div class="flex items-center gap-4">
                            <i class="pi pi-map-marker text-xl"></i>
                            <div class="text-sm">
                                <div class="font-semibold text-amber-400">ENCONTRE-NOS:</div>
                                <div>Maputo, Mozambique</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <i class="pi pi-clock text-xl"></i>
                        <div class="text-sm">
                            <div class="font-semibold text-amber-400">HORÁRIO:</div>
                            <div>Segunda a Sexta: 08:00 - 16:00</div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Navigation -->
        <nav class="bg-white shadow-md sticky top-0 z-50">
            <div class="container mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div>
                            <h1 class="text-3xl font-bold text-blue-900">School<span class="text-cyan-600"></span></h1>
                        </div>
                    </div>
                    <ul class="flex items-center gap-8 list-none">
                        <li><a href="#home" class="text-gray-700 hover:text-cyan-600 font-medium transition-colors">Início</a></li>
                        <li><a href="#sobre" class="text-gray-700 hover:text-cyan-600 font-medium transition-colors">Sobre Nós</a></li>
                        <li><a href="#metodologia" class="text-gray-700 hover:text-cyan-600 font-medium transition-colors">Metodologia</a></li>
                        <li><a href="#programa" class="text-gray-700 hover:text-cyan-600 font-medium transition-colors">Programa</a></li>
                        <li><a href="#equipe" class="text-gray-700 hover:text-cyan-600 font-medium transition-colors">Equipe</a></li>
                        <li><a href="#blog" class="text-gray-700 hover:text-cyan-600 font-medium transition-colors">Blog</a></li>
                        <li><a href="#contato" class="text-gray-700 hover:text-cyan-600 font-medium transition-colors">Contato</a></li>
                    </ul>
                    <div class="flex items-center gap-4">
                        <!-- <button class="p-2"><i class="pi pi-search text-gray-700"></i></button> -->
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section id="home" class="bg-cover bg-center h-[600px] relative" style="background-image: url('/demo/images/school/8.jpg')">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900/70 via-cyan-800/60 to-transparent"></div>
            <div class="container mx-auto px-6 h-full flex items-center relative z-10">
                <div class="text-white max-w-3xl">
                    <h2 class="text-sm font-semibold mb-3 tracking-wider text-amber-300">HOMESCHOOLING COSTEIRO</h2>
                    <h1 class="text-6xl font-bold mb-6 leading-tight text-white">Educação Personalizada<br>Onde o Mar Encontra<br>o Conhecimento</h1>
                    <p class="text-xl mb-8 text-blue-100">Homeschooling com a natureza como sala de aula e a praia como laboratório</p>
                    <div class="flex gap-4 mb-8">
                        <button class="bg-cyan-500 hover:bg-cyan-600 text-white px-8 py-4 rounded-full font-semibold transition-all shadow-lg hover:shadow-xl">
                            Conheça Nossa Metodologia
                        </button>
                        <button class="border-2 border-white bg-white/10 backdrop-blur-sm hover:bg-white hover:text-blue-900 text-white px-8 py-4 rounded-full font-semibold transition-all">
                            Agende uma Visita
                        </button>
                    </div>
                    <!-- Key Features Icons -->
                    <div class="flex gap-8 mt-8">
                        <div v-for="feature in keyFeatures" :key="feature.text" class="flex items-center gap-3 bg-white/10 backdrop-blur-sm px-4 py-3 rounded-full">
                            <i :class="`pi ${feature.icon} text-amber-300 text-xl`" style="font-size: 1.5rem;"></i>
                            <span class="text-sm font-medium">{{ feature.text }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Us CTA Section -->
        <section class="bg-gradient-to-r from-blue-900 to-cyan-700 text-white py-12">
            <div class="container mx-auto px-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-6">
                        
                        <div>
                            <h3 class="text-2xl font-bold mb-2 text-white">DESCUBRA NOSSA ESCOLA</h3>
                            <p class="text-blue-100">Educação holística, respeito ao ritmo individual, conexão com a natureza</p>
                        </div>
                    </div>
                    <button class="border-2 border-white text-black px-8 py-3 rounded-full font-semibold hover:bg-white hover:text-blue-900 transition-all shadow-lg">
                        SAIBA MAIS
                    </button>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="bg-cover bg-center py-20" style="background-image: url('/demo/images/school/2.jpg')">
            <div class="bg-gradient-to-r from-blue-50/95 via-cyan-50/95 to-blue-50/95 py-16">
                <div class="container mx-auto px-6">
                    <div class="grid grid-cols-4 gap-8">
                        <div v-for="stat in statistics" :key="stat.label" class="text-center">
                            <i :class="`pi ${stat.icon} text-cyan-600 mb-4`" style="font-size: 2rem;"></i>
                            <h3 class="text-5xl font-bold text-blue-900 mb-2">{{ stat.count }}</h3>
                            <p class="text-gray-700 font-medium">{{ stat.label }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Us Section -->
        <section id="sobre" class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-blue-900 mb-4">SOBRE NÓS</h2>
                    <p class="text-gray-600">Uma nova forma de aprender, conectada à natureza</p>
                    <div class="w-16 h-1 bg-cyan-600 mx-auto mt-4"></div>
                </div>
                <div class="grid grid-cols-2 gap-12 mb-12">
                    <div>
                        <h3 class="text-3xl font-bold text-blue-900 mb-4">NOSSA HISTÓRIA</h3>
                        <h4 class="text-xl text-cyan-600 mb-6">EDUCAÇÃO QUE RESPEITA A NATUREZA E O INDIVÍDUO</h4>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            A School nasceu do sonho de unir educação de qualidade com o ambiente privilegiado do litoral. Fundada por educadores apaixonados pela natureza, nossa escola oferece uma proposta única de homeschooling que utiliza o ecossistema costeiro como parte integrante do currículo educacional.
                        </p>
                        <p class="text-gray-600 mb-8 leading-relaxed">
                            Acreditamos que cada criança aprende em seu próprio ritmo e que o contato com a natureza potencializa o desenvolvimento integral. Nossa metodologia combina rigor acadêmico com liberdade de exploração, preparando cidadãos conscientes e conectados com o meio ambiente.
                        </p>
                        
                        <h4 class="text-lg font-bold text-blue-900 mb-4 mt-8">MISSÃO, VISÃO E VALORES</h4>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start gap-3">
                                <i class="pi pi-check-circle text-cyan-600 mt-1" style="font-size: 1.5rem;"></i>
                                <div>
                                    <span class="font-semibold text-gray-800">Missão:</span>
                                    <span class="text-gray-700"> Proporcionar educação holística que respeita o ritmo individual de cada estudante</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="pi pi-check-circle text-cyan-600 mt-1" style="font-size: 1.5rem;"></i>
                                <div>
                                    <span class="font-semibold text-gray-800">Visão:</span>
                                    <span class="text-gray-700"> Ser referência em educação integrada à natureza no Brasil</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="pi pi-check-circle text-cyan-600 mt-1" style="font-size: 1.5rem;"></i>
                                <div>
                                    <span class="font-semibold text-gray-800">Valores:</span>
                                    <span class="text-gray-700"> Respeito, sustentabilidade, autonomia, criatividade e conexão</span>
                                </div>
                            </li>
                        </ul>
                        
                        <button class="border-2 border-cyan-600 text-cyan-600 px-8 py-3 rounded-full font-semibold hover:bg-cyan-600 hover:text-white transition-all shadow-md">
                            CONHEÇA MAIS
                        </button>
                    </div>
                    <div class="grid grid-cols-2 gap-6">
                        <div v-for="advantage in advantages" :key="advantage.title" :class="[advantage.bgColor, 'p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow']">
                            <i :class="`pi ${advantage.icon} ${advantage.iconColor} text-4xl mb-4 block`" style="font-size: 1.5rem;"></i>
                            <h4 class="font-bold text-blue-900 mb-2 text-lg">{{ advantage.title }}</h4>
                            <p class="text-sm text-gray-600 leading-relaxed">{{ advantage.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Methodology Section -->
        <section id="metodologia" class="py-20 bg-gradient-to-br from-blue-50 to-cyan-50">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-blue-900 mb-4">NOSSA METODOLOGIA</h2>
                    <p class="text-gray-600">Educação personalizada com a natureza como aliada</p>
                    <div class="w-16 h-1 bg-cyan-600 mx-auto mt-4"></div>
                </div>
                
                <div class="max-w-6xl mx-auto">
                    <div class="grid grid-cols-3 gap-8 mb-12">
                        <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-xl transition-all">
                            <div class="bg-cyan-100 rounded-full w-16 h-16 flex items-center justify-center mb-6">
                                <i class="pi pi-user text-cyan-600 text-3xl" style="font-size: 1.5rem;"></i>
                            </div>
                            <h3 class="text-xl font-bold text-blue-900 mb-4">Educação Personalizada</h3>
                            <p class="text-gray-600 leading-relaxed">Adaptamos o currículo às necessidades, interesses e ritmo de cada estudante, garantindo aprendizagem significativa e desenvolvimento pleno.</p>
                        </div>
                        
                        <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-xl transition-all">
                            <div class="bg-green-100 rounded-full w-16 h-16 flex items-center justify-center mb-6">
                                <i class="pi pi-sun text-green-600 text-3xl" style="font-size: 1.5rem;"></i>
                            </div>
                            <h3 class="text-xl font-bold text-blue-900 mb-4">Aprendizagem na Natureza</h3>
                            <p class="text-gray-600 leading-relaxed">Ciências marinhas e costeiras integradas ao currículo. A praia, mangues e vegetação costeira são extensões naturais de nossa sala de aula.</p>
                        </div>
                        
                        <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-xl transition-all">
                            <div class="bg-blue-100 rounded-full w-16 h-16 flex items-center justify-center mb-6">
                                <i class="pi pi-building text-blue-600 text-3xl" style="font-size: 1.5rem;"></i>
                            </div>
                            <h3 class="text-xl font-bold text-blue-900 mb-4">Estrutura Híbrida</h3>
                            <p class="text-gray-600 leading-relaxed">Combinamos aulas em espaços físicos confortáveis com atividades ao ar livre e suporte online quando necessário.</p>
                        </div>
                    </div>
                    
                    <div class="bg-white p-10 rounded-xl shadow-lg">
                        <h4 class="text-2xl font-bold text-blue-900 mb-6 text-center">Avaliação Contínua e Desenvolvimento Integral</h4>
                        <p class="text-gray-600 text-center mb-8 leading-relaxed max-w-3xl mx-auto">
                            Utilizamos portfólios, projetos e observação cuidadosa do desenvolvimento para acompanhar o progresso de cada estudante. Nossa abordagem vai além das notas, valorizando o crescimento acadêmico, emocional e social.
                        </p>
                        <div class="flex justify-center gap-6">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-check-circle text-cyan-600" style="font-size: 1.5rem;"></i>
                                <span class="text-gray-700">Portfólios individuais</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-check-circle text-cyan-600" style="font-size: 1.5rem;"></i>
                                <span class="text-gray-700">Projetos práticos</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-check-circle text-cyan-600" style="font-size: 1.5rem;"></i>
                                <span class="text-gray-700">Desenvolvimento socioemocional</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Learning Spaces Section -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-blue-900 mb-4">ESPAÇOS DE APRENDIZAGEM</h2>
                    <p class="text-gray-600">Ambientes pensados para inspirar e conectar</p>
                    <div class="w-16 h-1 bg-cyan-600 mx-auto mt-4"></div>
                </div>
                
                <div class="grid grid-cols-2 gap-8 max-w-5xl mx-auto">
                    <div v-for="space in learningSpaces" :key="space.title" class="bg-gradient-to-br from-blue-50 to-white p-8 rounded-xl shadow-md hover:shadow-xl transition-all border border-cyan-100">
                        <i :class="`pi ${space.icon} ${space.iconColor} text-5xl mb-6 block`" style="font-size: 1.5rem;"></i>
                        <h3 class="text-2xl font-bold text-blue-900 mb-4">{{ space.title }}</h3>
                        <p class="text-gray-600 leading-relaxed">{{ space.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Educational Program Section -->
        <section id="programa" class="py-20 bg-gradient-to-br from-cyan-50 via-blue-50 to-cyan-50">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-blue-900 mb-4">PROGRAMA EDUCACIONAL</h2>
                    <p class="text-gray-600">Do infantil ao ensino médio, sempre conectados à natureza</p>
                    <div class="w-16 h-1 bg-cyan-600 mx-auto mt-4"></div>
                </div>
                
                <div class="grid grid-cols-2 gap-8 max-w-6xl mx-auto mb-12">
                    <div v-for="program in educationalPrograms" :key="program.level" class="bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-2xl transition-all">
                        <div :class="[program.color, 'p-6 text-white']">
                            <h3 class="text-2xl font-bold mb-2">{{ program.level }}</h3>
                            <p class="text-blue-100 font-medium">{{ program.age }}</p>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-600 leading-relaxed">{{ program.description }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-10 shadow-lg max-w-4xl mx-auto">
                    <h4 class="text-2xl font-bold text-blue-900 mb-6 text-center">Componentes Específicos</h4>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="flex items-start gap-3">
                            <i class="pi pi-check-circle text-cyan-600 text-xl mt-1" style="font-size: 1.5rem;"></i>
                            <div>
                                <h5 class="font-bold text-blue-900 mb-1">Ciências Marinhas</h5>
                                <p class="text-sm text-gray-600">Estudo dos ecossistemas costeiros e oceânicos</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="pi pi-check-circle text-cyan-600 text-xl mt-1" style="font-size: 1.5rem;"></i>
                            <div>
                                <h5 class="font-bold text-blue-900 mb-1">Sustentabilidade Costeira</h5>
                                <p class="text-sm text-gray-600">Consciência ambiental e preservação</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="pi pi-check-circle text-cyan-600 text-xl mt-1" style="font-size: 1.5rem;"></i>
                            <div>
                                <h5 class="font-bold text-blue-900 mb-1">Educação Física na Natureza</h5>
                                <p class="text-sm text-gray-600">Surf, stand-up paddle, natação oceânica</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="pi pi-check-circle text-cyan-600 text-xl mt-1" style="font-size: 1.5rem;"></i>
                            <div>
                                <h5 class="font-bold text-blue-900 mb-1">Artes com Elementos Naturais</h5>
                                <p class="text-sm text-gray-600">Expressão artística sustentável</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Gallery Section -->
        <section class="py-0">
            <div class="grid grid-cols-6 gap-0">
                <div v-for="i in 6" :key="i" class="aspect-square bg-cover bg-center hover:opacity-90 transition-opacity" :style="`background-image: url('/demo/images/school/${i}.jpg')`"></div>
            </div>
        </section>

        <!-- Gallery CTA -->
        <section class="bg-gradient-to-r from-cyan-700 to-blue-700 text-white py-12">
            <div class="container mx-auto px-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-6">
                        
                        <div>
                            <h3 class="text-2xl font-bold mb-2 text-white">VIDA NA ESCOLA</h3>
                            <p class="text-blue-100">Descubra as atividades diárias e experiências de aprendizagem</p>
                        </div>
                    </div>
                    <button class="border-2 border-white text-black px-8 py-3 rounded-full font-semibold hover:bg-white hover:text-blue-900 transition-all shadow-lg">
                        VER GALERIA
                    </button>
                </div>
            </div>
        </section>

        <!-- For Families Section -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-blue-900 mb-4">PARA FAMÍLIAS</h2>
                    <p class="text-gray-600">Parceria família-escola para educação de excelência</p>
                    <div class="w-16 h-1 bg-cyan-600 mx-auto mt-4"></div>
                </div>
                
                <div class="max-w-5xl mx-auto grid grid-cols-3 gap-8">
                    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 p-8 rounded-xl shadow-md hover:shadow-lg transition-all">
                        <i class="pi pi-book text-blue-600 text-4xl mb-6 block" style="font-size: 1.5rem;"></i>
                        <h3 class="text-xl font-bold text-blue-900 mb-4">Como Funciona o Homeschooling</h3>
                        <ul class="space-y-2 text-gray-600 text-sm">
                            <li class="flex items-start gap-2">
                                <i class="pi pi-circle-fill text-cyan-600 text-xs mt-1.5"></i>
                                <span>Legislação brasileira de homeschooling</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="pi pi-circle-fill text-cyan-600 text-xs mt-1.5"></i>
                                <span>Nosso papel como escola parceira</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="pi pi-circle-fill text-cyan-600 text-xs mt-1.5"></i>
                                <span>Documentação e suporte burocrático</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-8 rounded-xl shadow-md hover:shadow-lg transition-all">
                        <i class="pi pi-users text-green-600 text-4xl mb-6 block" style="font-size: 1.5rem;"></i>
                        <h3 class="text-xl font-bold text-blue-900 mb-4">Envolvimento Familiar</h3>
                        <ul class="space-y-2 text-gray-600 text-sm">
                            <li class="flex items-start gap-2">
                                <i class="pi pi-circle-fill text-green-600 text-xs mt-1.5"></i>
                                <span>Reuniões regulares com tutores</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="pi pi-circle-fill text-green-600 text-xs mt-1.5"></i>
                                <span>Workshops para pais</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="pi pi-circle-fill text-green-600 text-xs mt-1.5"></i>
                                <span>Eventos familiares na praia</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 p-8 rounded-xl shadow-md hover:shadow-lg transition-all">
                        <i class="pi pi-comments text-amber-600 text-4xl mb-6 block" style="font-size: 1.5rem;"></i>
                        <h3 class="text-xl font-bold text-blue-900 mb-4">Depoimentos</h3>
                        <p class="text-gray-600 text-sm italic leading-relaxed">
                            "A melhor decisão que tomamos! Nosso filho floresce em um ambiente que respeita seu ritmo e potencializa sua conexão com a natureza."
                        </p>
                        <p class="text-cyan-600 font-medium text-sm mt-3">— Família Silva</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Team Section -->
        <section id="equipe" class="py-20 bg-gradient-to-br from-blue-50 to-cyan-50">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-blue-900 mb-4">NOSSA EQUIPE</h2>
                    <p class="text-gray-600">Educadores apaixonados pela natureza e pelo desenvolvimento humano</p>
                    <div class="w-16 h-1 bg-cyan-600 mx-auto mt-4"></div>
                </div>
                
                <div class="grid grid-cols-3 gap-8 max-w-5xl mx-auto mb-12">
                    <div v-for="member in teamMembers" :key="member.name" class="bg-white rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-all">
                        <div class="aspect-square bg-cover bg-center" :style="`background-image: url('${member.image}')`"></div>
                        <div class="p-6 text-center">
                            <h3 class="text-xl font-bold text-blue-900 mb-2">{{ member.name }}</h3>
                            <p class="text-cyan-600 font-medium mb-2">{{ member.role }}</p>
                            <p class="text-sm text-gray-600">{{ member.specialty }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-8 max-w-3xl mx-auto shadow-md">
                    <h4 class="text-xl font-bold text-blue-900 mb-4 text-center">Equipe Multidisciplinar</h4>
                    <p class="text-gray-600 text-center leading-relaxed">
                        Nossa equipe reúne pedagogos, biólogos marinhos, educadores físicos, psicólogos e especialistas em educação ambiental. Todos compartilham a filosofia de educação holística, conectada à natureza e respeitosa ao desenvolvimento individual.
                    </p>
                </div>
            </div>
        </section>

        <!-- Special Activities Section -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-blue-900 mb-4">ATIVIDADES ESPECIAIS</h2>
                    <p class="text-gray-600">Projetos que conectam aprendizado, natureza e comunidade</p>
                    <div class="w-16 h-1 bg-cyan-600 mx-auto mt-4"></div>
                </div>
                
                <div class="grid grid-cols-2 gap-8 max-w-6xl mx-auto">
                    <div v-for="activity in specialActivities" :key="activity.title" class="bg-gradient-to-br from-white to-blue-50 p-8 rounded-xl shadow-md hover:shadow-xl transition-all border border-cyan-100">
                        <i :class="`pi ${activity.icon} ${activity.color} text-5xl mb-6 block`" style="font-size: 1.5rem;"></i>
                        <h3 class="text-2xl font-bold text-blue-900 mb-4">{{ activity.title }}</h3>
                        <p class="text-gray-600 leading-relaxed">{{ activity.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Enrollment Process Section -->
        <section class="py-20 bg-gradient-to-br from-cyan-50 to-blue-50">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-blue-900 mb-4">PROCESSO DE ADMISSÃO</h2>
                    <p class="text-gray-600">Quatro passos para fazer parte da nossa comunidade</p>
                    <div class="w-16 h-1 bg-cyan-600 mx-auto mt-4"></div>
                </div>
                <div class="grid grid-cols-4 gap-8 max-w-6xl mx-auto">
                    <div v-for="step in enrollmentSteps" :key="step.number" class="text-center">
                        <div :class="[step.number % 2 === 0 ? 'bg-cyan-600' : 'bg-blue-900', 'rounded-full w-24 h-24 mx-auto flex items-center justify-center text-white mb-6 relative']">
                            <span class="text-4xl font-bold">{{ step.number }}</span>
                            <div v-if="step.number < 4" class="absolute left-full top-1/2 w-full h-0.5 bg-cyan-300 -translate-y-1/2"></div>
                        </div>
                        <h3 class="text-xl font-bold text-blue-900 mb-4">{{ step.title }}</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ step.description }}</p>
                    </div>
                </div>
                
                <div class="mt-16 text-center">
                    <div class="bg-white rounded-xl p-8 max-w-3xl mx-auto shadow-lg">
                        <h4 class="text-xl font-bold text-blue-900 mb-4">FAQ sobre Homeschooling</h4>
                        <div class="text-left space-y-4">
                            <div>
                                <h5 class="font-semibold text-blue-900 mb-2">É legal em Moçambique?</h5>
                                <p class="text-sm text-gray-600">Atuamos dentro da legislação vigente, oferecendo suporte completo para adequação às exigências legais.</p>
                            </div>
                            <div>
                                <h5 class="font-semibold text-blue-900 mb-2">Meu filho terá certificação?</h5>
                                <p class="text-sm text-gray-600">Sim! Oferecemos todo o suporte para validação e certificação educacional.</p>
                            </div>
                            <div>
                                <h5 class="font-semibold text-blue-900 mb-2">Como funciona o período de adaptação?</h5>
                                <p class="text-sm text-gray-600">Oferecemos um período gradual de 2 semanas para a criança conhecer os espaços, tutores e colegas.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Enrollment -->
        <section class="bg-gradient-to-r from-blue-900 to-cyan-700 text-white py-12">
            <div class="container mx-auto px-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-6">
                        <div>
                            <h3 class="text-2xl font-bold mb-2 text-white">INICIE SUA JORNADA</h3>
                            <p class="text-blue-100">Proporcione ao seu filho uma educação conectada com a natureza</p>
                        </div>
                    </div>
                    <button class="border-2 border-white text-black px-8 py-3 rounded-full font-semibold hover:bg-white hover:text-blue-900 transition-all shadow-lg">
                        AGENDAR VISITA
                    </button>
                </div>
            </div>
        </section>

        <!-- Blog / News Section -->
        <section id="blog" class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-blue-900 mb-4">BLOG & NOVIDADES</h2>
                    <p class="text-gray-600">Acompanhe as últimas notícias e insights sobre educação na natureza</p>
                    <div class="w-16 h-1 bg-cyan-600 mx-auto mt-4"></div>
                </div>
                <div class="grid grid-cols-3 gap-8 max-w-6xl mx-auto">
                    <div v-for="post in blogPosts" :key="post.title" class="bg-white rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-all">
                        <div class="aspect-[4/3] bg-cover bg-center" :style="`background-image: url('${post.image}')`"></div>
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="text-4xl font-bold text-cyan-600">{{ post.date }}</span>
                                <span class="text-sm text-gray-500">/ {{ post.month }}</span>
                            </div>
                            <h3 class="text-xl font-bold text-blue-900 mb-3 hover:text-cyan-600 cursor-pointer transition-colors">{{ post.title }}</h3>
                            <p class="text-gray-600 text-sm leading-relaxed">{{ post.excerpt }}</p>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-12">
                    <button class="border-2 border-cyan-600 text-cyan-600 px-8 py-3 rounded-full font-semibold hover:bg-cyan-600 hover:text-white transition-all shadow-md">
                        VER TODOS OS ARTIGOS
                    </button>
                </div>
            </div>
        </section>

        <!-- Newsletter Section -->
        <section class="bg-gradient-to-br from-blue-50 to-cyan-50 py-16">
            <div class="container mx-auto px-6">
                <div class="text-center">
                    <div class="bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full w-20 h-20 mx-auto mb-6 flex items-center justify-center shadow-lg">
                        <i class="pi pi-envelope text-white text-3xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-blue-900 mb-3">Receba Nossas Novidades</h2>
                    <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
                        Inscreva-se em nossa newsletter e receba conteúdos sobre educação na natureza, dicas de atividades e atualizações da escola
                    </p>
                    <div class="flex gap-4 max-w-md mx-auto">
                        <input 
                            type="email" 
                            placeholder="Seu melhor e-mail" 
                            class="flex-1 px-6 py-3 rounded-full border-2 border-cyan-300 focus:outline-none focus:border-cyan-600 transition-colors"
                        />
                        <button class="bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white px-8 py-3 rounded-full font-semibold transition-all shadow-md hover:shadow-lg">
                            INSCREVER
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contato" class="py-16 bg-gradient-to-r from-blue-900 to-cyan-700 text-white">
            <div class="container mx-auto px-6">
                <div class="grid grid-cols-3 gap-12 max-w-5xl mx-auto">
                    <div class="text-center">
                        <div class="bg-white/10 backdrop-blur-sm rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                            <i class="pi pi-phone text-2xl"></i>
                        </div>
                        <h4 class="font-bold mb-2 text-amber-300">TELEFONE</h4>
                        <p class="text-blue-100">+258 84 123 4567</p>
                        <p class="text-sm text-blue-200">Segunda a Sexta, 8h-17h</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-white/10 backdrop-blur-sm rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                            <i class="pi pi-envelope text-2xl"></i>
                        </div>
                        <h4 class="font-bold mb-2 text-amber-300">E-MAIL</h4>
                        <p class="text-blue-100">info@school.co.mz</p>
                        <p class="text-sm text-blue-200">Respondemos em 24h</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-white/10 backdrop-blur-sm rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                            <i class="pi pi-map-marker text-2xl"></i>
                        </div>
                        <h4 class="font-bold mb-2 text-amber-300">LOCALIZAÇÃO</h4>
                        <p class="text-blue-100">Distrito de Matutuine</p>
                        <p class="text-sm text-blue-200">Maputo, Moçambique</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gradient-to-br from-blue-950 to-blue-900 text-white pt-16 pb-8">
            <div class="container mx-auto px-6">
                <div class="grid grid-cols-3 gap-12 mb-12">
                    <!-- About Us -->
                    <div>
                        <h3 class="text-xl font-bold mb-6 pb-3 border-b-2 border-cyan-500 inline-block text-white">SOBRE SCHOOL</h3>
                        <p class="text-blue-200 mb-6 leading-relaxed">
                            Escola pioneira em homeschooling costeiro em Moçambique. Oferecemos educação personalizada e holística, utilizando o ambiente marinho como laboratório de aprendizagem. Nossa metodologia respeita o ritmo individual de cada estudante enquanto promove conexão profunda com a natureza e consciência ambiental.
                        </p>
                        <div class="flex items-center gap-2 mb-4">
                            <i class="pi pi-sun text-3xl text-cyan-400"></i>
                            <div>
                                <h2 class="text-2xl font-bold">School<span class="text-cyan-400">.</span></h2>
                                <span class="text-xs text-blue-300">Homeschool na praia</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Posts -->
                    <div>
                        <h3 class="text-xl font-bold mb-6 pb-3 border-b-2 border-cyan-500 inline-block text-white">ARTIGOS RECENTES</h3>
                        <div class="space-y-4">
                            <div v-for="post in blogPosts" :key="post.title" class="flex gap-4">
                                <div class="w-16 h-16 bg-cover bg-center rounded flex-shrink-0" :style="`background-image: url('${post.image}')`"></div>
                                <div>
                                    <h4 class="font-semibold text-sm mb-1 hover:text-cyan-400 cursor-pointer transition-colors text-blue-200">{{ post.title }}</h4>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl font-bold text-cyan-400">{{ post.date }}</span>
                                        <span class="text-xs text-blue-300">/ {{ post.month }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Why Choose Us -->
                    <div>
                        <h3 class="text-xl font-bold mb-6 pb-3 border-b-2 border-cyan-500 inline-block text-white">POR QUE SCHOOL</h3>
                        <p class="text-blue-200 mb-6 leading-relaxed">
                            Educação que une rigor acadêmico com liberdade de exploração em ambiente natural privilegiado.
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <i class="pi pi-check text-cyan-400 mt-1"></i>
                                <span class="text-blue-200">Pequenos grupos (8-10 alunos/tutor)</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="pi pi-check text-cyan-400 mt-1"></i>
                                <span class="text-blue-200">Educação personalizada e holística</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="pi pi-check text-cyan-400 mt-1"></i>
                                <span class="text-blue-200">Aprendizagem baseada na natureza</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="pi pi-check text-cyan-400 mt-1"></i>
                                <span class="text-blue-200">Ciências marinhas integradas</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="pi pi-check text-cyan-400 mt-1"></i>
                                <span class="text-blue-200">Projetos de conservação ambiental</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="pi pi-check text-cyan-400 mt-1"></i>
                                <span class="text-blue-200">Educação física na natureza</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="pi pi-check text-cyan-400 mt-1"></i>
                                <span class="text-blue-200">Horários flexíveis respeitando marés</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Bottom Footer -->
                <div class="border-t border-blue-800 pt-8">
                    <!-- Links -->
                    <div class="flex justify-center gap-8 mb-6 text-sm">
                        <a href="#" class="text-blue-200 hover:text-cyan-400 transition-colors">Política de Privacidade</a>
                        <a href="#" class="text-blue-200 hover:text-cyan-400 transition-colors">Termos de Uso</a>
                        <a href="#" class="text-blue-200 hover:text-cyan-400 transition-colors">FAQ</a>
                        <a href="#" class="text-blue-200 hover:text-cyan-400 transition-colors">Legislação Homeschooling</a>
                    </div>
                    
                    <!-- Social Media -->
                    <div class="flex justify-center gap-6 mb-6">
                        <a href="#" class="w-10 h-10 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center hover:bg-cyan-500 transition-all shadow-md">
                            <i class="pi pi-facebook text-white"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center hover:bg-cyan-500 transition-all shadow-md">
                            <i class="pi pi-instagram text-white"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center hover:bg-cyan-500 transition-all shadow-md">
                            <i class="pi pi-youtube text-white"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center hover:bg-cyan-500 transition-all shadow-md">
                            <i class="pi pi-whatsapp text-white"></i>
                        </a>
                    </div>
                    
                    <!-- Copyright -->
                    <div class="text-center text-blue-300 text-sm">
                        <p class="mb-2">© 2026 School - Homeschool na Praia | Educação Conectada com a Natureza</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
/* Global styles for smooth scrolling */
html {
    scroll-behavior: smooth;
}
</style>

<style scoped>
/* Gradient backgrounds */
.bg-gradient-ocean {
    background: linear-gradient(135deg, #0c4a6e 0%, #0e7490 50%, #06b6d4 100%);
}

/* Animation for hover effects */
.hover-lift {
    transition: transform 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
}
</style>
