<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

// Countdown timer state
const countdown = ref({
    days: 0,
    hours: 0,
    minutes: 0,
    seconds: 0
});

// Target date for countdown (first school day)
const targetDate = new Date('2026-09-01T09:00:00').getTime();

// Update countdown
const updateCountdown = () => {
    const now = new Date().getTime();
    const distance = targetDate - now;

    if (distance > 0) {
        countdown.value = {
            days: Math.floor(distance / (1000 * 60 * 60 * 24)),
            hours: Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
            minutes: Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)),
            seconds: Math.floor((distance % (1000 * 60)) / 1000)
        };
    }
};

let countdownInterval: number;

onMounted(() => {
    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 1000);
});

onUnmounted(() => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
});

// Services data
const services = [
    {
        icon: 'pi-home',
        title: 'Modern Facilities',
        description: 'Air-conditioned classrooms with smart boards, computer labs, and a well-stocked library for optimal learning.',
        iconColor: 'text-red-600',
        bgColor: 'bg-red-50'
    },
    {
        icon: 'pi-heart',
        title: 'Sports & Recreation',
        description: 'Football field, basketball court, and playground equipment to promote physical fitness and teamwork.',
        iconColor: 'text-orange-600',
        bgColor: 'bg-orange-50'
    },
    {
        icon: 'pi-book',
        title: 'Bilingual Education',
        description: 'Quality instruction in both Portuguese and English, preparing students for a globalized world.',
        iconColor: 'text-blue-600',
        bgColor: 'bg-blue-50'
    },
    {
        icon: 'pi-star',
        title: 'STEM Programs',
        description: 'Hands-on science and technology labs fostering creativity, critical thinking, and problem-solving skills.',
        iconColor: 'text-purple-600',
        bgColor: 'bg-purple-50'
    },
    {
        icon: 'pi-shopping-cart',
        title: 'Nutritious Meals',
        description: 'Healthy breakfast and lunch prepared daily by our kitchen staff with balanced, child-friendly menus.',
        iconColor: 'text-pink-600',
        bgColor: 'bg-pink-50'
    },
    {
        icon: 'pi-car',
        title: 'Safe Transportation',
        description: 'Reliable school bus service with trained drivers covering major areas of Maputo and surrounding districts.',
        iconColor: 'text-yellow-600',
        bgColor: 'bg-yellow-50'
    }
];

// Enrollment steps
const enrollmentSteps = [
    {
        number: 1,
        title: 'Contact',
        description: 'Reach out to us via phone, email, or visit our campus. Our admissions team will guide you through the enrollment process and answer all your questions.'
    },
    {
        number: 2,
        title: 'Application',
        description: 'Complete the application form with your child\'s information. Submit required documents including birth certificate, health records, and previous school reports.'
    },
    {
        number: 3,
        title: 'Assessment',
        description: 'Your child will participate in a friendly assessment to help us understand their learning needs and place them in the appropriate grade level.'
    },
    {
        number: 4,
        title: 'Admission',
        description: 'Once accepted, complete the registration process, pay tuition fees, and receive your welcome package. Your child is now part of the Prestige family!'
    }
];

// Blog posts
const blogPosts = [
    {
        date: '05',
        month: 'FEBRUARY',
        title: 'Celebrating Cultural Diversity at Prestige School',
        excerpt: 'Our students participated in a vibrant cultural day, showcasing Mozambican traditions, music, dance, and cuisine from different regions...',
        image: '/demo/images/school/1.jpg'
    },
    {
        date: '28',
        month: 'JANUARY',
        title: 'STEM Excellence: Students Win Regional Science Fair',
        excerpt: 'Congratulations to our Year 5 students who won first place at the Maputo Regional Science Fair with their innovative solar-powered water purification project...',
        image: '/demo/images/school/1.jpg'
    },
    {
        date: '15',
        month: 'JANUARY',
        title: 'Building Tomorrow\'s Leaders Through Education',
        excerpt: 'At Prestige School, we believe in nurturing not just academic excellence but also character, leadership, and community responsibility...',
        image: '/demo/images/school/1.jpg'
    }
];

// Pricing plans
const pricingPlans = [
    {
        name: 'Music & Arts',
        price: '3,500',
        period: 'MONTHLY',
        description: 'Piano, guitar, violin lessons, painting, drawing, and sculpture classes. Develop creativity and artistic expression.',
        color: 'bg-blue-900'
    },
    {
        name: 'Sports Academy',
        price: '4,200',
        period: 'MONTHLY',
        description: 'Football, basketball, swimming, and athletics training. Professional coaches and access to modern sports facilities.',
        color: 'bg-red-500',
        featured: true
    },
    {
        name: 'STEM Club',
        price: '4,800',
        period: 'MONTHLY',
        description: 'Robotics, coding, science experiments, and mathematics enrichment. Prepare for future careers in technology.',
        color: 'bg-blue-900'
    }
];

// Statistics
const statistics = [
    { icon: 'pi-compass', count: '15', label: 'Years of Excellence' },
    { icon: 'pi-users', count: '450', label: 'Happy Students' },
    { icon: 'pi-shield', count: '45', label: 'Qualified Teachers' },
    { icon: 'pi-trophy', count: '92', label: 'Awards Won' }
];
</script>

<template>
    <div class="min-h-screen bg-white">
        <!-- Header/Navigation -->
        <header class="bg-[#0a2847] text-white">
            <div class="container mx-auto px-6 py-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-12">
                        <div class="flex items-center gap-4">
                            <i class="pi pi-map-marker text-xl"></i>
                            <div class="text-sm">
                                <div class="font-semibold text-orange-500">FIND US:</div>
                                <div>Maputo, Mozambique</div>
                                <div>Av. Julius Nyerere, 1234</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <i class="pi pi-clock text-xl"></i>
                        <div class="text-sm">
                            <div class="font-semibold text-orange-500">OPEN TIME:</div>
                            <div>07:30 - 15:30</div>
                            <div>Saturday and Sunday - Closed</div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Navigation -->
        <nav class="bg-white shadow-sm sticky top-0 z-50">
            <div class="container mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <h1 class="text-3xl font-bold text-[#0a2847]">Prestige<span class="text-orange-500">.</span></h1>
                        <span class="text-sm text-gray-600">primary school</span>
                    </div>
                    <ul class="flex items-center gap-8 list-none">
                        <li><a href="#" class="text-gray-700 hover:text-orange-500 font-medium">Home</a></li>
                        <li><a href="#" class="text-gray-700 hover:text-orange-500 font-medium">Blog</a></li>
                        <li><a href="#" class="text-gray-700 hover:text-orange-500 font-medium">Pages</a></li>
                        <li><a href="#" class="text-gray-700 hover:text-orange-500 font-medium">Contact</a></li>
                        <li><a href="#" class="text-gray-700 hover:text-orange-500 font-medium">Plans</a></li>
                        <li><a href="#" class="text-gray-700 hover:text-orange-500 font-medium">Shop</a></li>
                        <li><a href="#" class="text-gray-700 hover:text-orange-500 font-medium">About</a></li>
                    </ul>
                    <div class="flex items-center gap-4">
                        <button class="p-2"><i class="pi pi-search text-gray-700"></i></button>
                        <!-- <button class="p-2 relative">
                            <i class="pi pi-shopping-cart text-gray-700"></i>
                            <span class="absolute -top-1 -right-1 bg-orange-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">0</span>
                        </button> -->
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="bg-cover bg-center h-[500px] relative" style="background-image: url('/demo/images/school/1.jpg')">
            <div class="absolute inset-0 bg-gradient-to-r from-black/50 to-transparent"></div>
            <div class="container mx-auto px-6 h-full flex items-center relative z-10">
                <div class="text-white max-w-2xl">
                    <h2 class="text-sm font-semibold mb-2 tracking-wider">PRESTIGE PRIMARY SCHOOL</h2>
                    <h1 class="text-5xl font-bold mb-6 leading-tight">EXCELLENCE IN EDUCATION<br>FOR EVERY CHILD</h1>
                    <button class="bg-orange-500 hover:bg-orange-600 text-white px-8 py-3 rounded-full font-semibold transition-colors">
                        CONTACT US
                    </button>
                </div>
            </div>
        </section>

        <!-- Enroll a Child Section -->
        <section class="bg-[#0a2847] text-white py-12">
            <div class="container mx-auto px-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-6">
                        <div class="bg-white rounded-full p-6">
                            <i class="pi pi-book text-orange-500 text-4xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold mb-2">ENROLL YOUR CHILD</h3>
                            <p class="text-gray-300">Join Mozambique's leading primary school for quality bilingual education</p>
                        </div>
                    </div>
                    <button class="border-2 border-white text-white px-8 py-3 rounded-full font-semibold hover:bg-white hover:text-[#0a2847] transition-colors">
                        CONTACT US
                    </button>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="bg-cover bg-center py-20" style="background-image: url('/demo/images/school/1.jpg')">
            <div class="bg-white/90 py-16">
                <div class="container mx-auto px-6">
                    <div class="grid grid-cols-4 gap-8">
                        <div v-for="stat in statistics" :key="stat.label" class="text-center">
                            <i :class="`pi ${stat.icon} text-6xl text-orange-500 mb-4`"></i>
                            <h3 class="text-5xl font-bold text-[#0a2847] mb-2">{{ stat.count }}</h3>
                            <p class="text-gray-600 font-medium">{{ stat.label }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Plan Upgrades Section -->
        <section class="py-20 bg-gray-50">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-[#0a2847] mb-4">EXTRACURRICULAR PROGRAMS</h2>
                    <p class="text-gray-600">Enriching education through specialized after-school activities</p>
                    <div class="w-16 h-1 bg-orange-500 mx-auto mt-4"></div>
                </div>
                <div class="grid grid-cols-3 gap-8 max-w-6xl mx-auto">
                    <div v-for="plan in pricingPlans" :key="plan.name" class="text-center">
                        <div :class="[plan.color, 'rounded-full w-40 h-40 mx-auto flex flex-col items-center justify-center text-white mb-6']">
                            <span class="text-sm">{{ plan.period }}</span>
                            <div class="flex items-start">
                                <span class="text-xs mt-3">MT</span>
                                <span class="text-4xl font-bold">{{ plan.price }}</span>
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-[#0a2847] mb-4">{{ plan.name }}</h3>
                        <p class="text-gray-600">{{ plan.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-[#0a2847] mb-4">OUR SERVICES</h2>
                    <p class="text-gray-600">We make your child happy day after day</p>
                    <div class="w-16 h-1 bg-orange-500 mx-auto mt-4"></div>
                </div>
                <div class="grid grid-cols-2 gap-12 mb-12">
                    <div>
                        <h3 class="text-2xl font-bold text-[#0a2847] mb-4">DAILY EXCELLENCE</h3>
                        <h4 class="text-xl text-orange-500 mb-6">QUALITY EDUCATION FOR MOZAMBICAN CHILDREN</h4>
                        <p class="text-gray-600 mb-6">
                            At Prestige School, we combine international educational standards with Mozambican culture and values. Our curriculum follows the national education system while incorporating English language instruction, modern teaching methods, and character development to prepare students for success in Mozambique and beyond.
                        </p>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-center gap-3">
                                <i class="pi pi-check-circle text-orange-500"></i>
                                <span class="text-gray-700">Bilingual curriculum in Portuguese and English</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="pi pi-check-circle text-orange-500"></i>
                                <span class="text-gray-700">Small class sizes (max 25 students per class)</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="pi pi-check-circle text-orange-500"></i>
                                <span class="text-gray-700">Modern technology-integrated classrooms</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="pi pi-check-circle text-orange-500"></i>
                                <span class="text-gray-700">Experienced and certified teaching staff</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="pi pi-check-circle text-orange-500"></i>
                                <span class="text-gray-700">Safe and secure campus with 24/7 security</span>
                            </li>
                        </ul>
                        <button class="border-2 border-orange-500 text-orange-500 px-8 py-3 rounded-full font-semibold hover:bg-orange-500 hover:text-white transition-colors">
                            MORE INFO
                        </button>
                    </div>
                    <div class="grid grid-cols-2 gap-6">
                        <div v-for="service in services" :key="service.title" :class="[service.bgColor, 'p-6 rounded-lg']">
                            <i :class="`pi ${service.icon} ${service.iconColor} text-4xl mb-4 block`"></i>
                            <h4 class="font-bold text-[#0a2847] mb-2">{{ service.title }}</h4>
                            <p class="text-sm text-gray-600">{{ service.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Gallery Section -->
        <section class="py-0">
            <div class="grid grid-cols-6 gap-0">
                <div v-for="i in 6" :key="i" class="aspect-square bg-cover bg-center" :style="`background-image: url('/demo/images/landing/gallery-${i}.jpg')`"></div>
            </div>
        </section>

        <!-- Play & Learn CTA -->
        <section class="bg-[#0a2847] text-white py-12">
            <div class="container mx-auto px-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-6">
                        <div class="bg-white rounded-full p-6">
                            <i class="pi pi-images text-orange-500 text-4xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold mb-2">CAMPUS LIFE</h3>
                            <p class="text-gray-300">Discover daily activities and learning experiences at Prestige School</p>
                        </div>
                    </div>
                    <button class="border-2 border-white text-white px-8 py-3 rounded-full font-semibold hover:bg-white hover:text-[#0a2847] transition-colors">
                        VIEW GALLERY
                    </button>
                </div>
            </div>
        </section>

        <!-- Enrollment Steps Section -->
        <section class="py-20 bg-gray-50">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-[#0a2847] mb-4">ENROLLMENT</h2>
                    <p class="text-gray-600">Steps done with heart, soul, mind & strength</p>
                    <div class="w-16 h-1 bg-orange-500 mx-auto mt-4"></div>
                </div>
                <div class="grid grid-cols-4 gap-8 max-w-6xl mx-auto">
                    <div v-for="step in enrollmentSteps" :key="step.number" class="text-center">
                        <div :class="[step.number % 2 === 0 ? 'bg-orange-500' : 'bg-[#0a2847]', 'rounded-full w-24 h-24 mx-auto flex items-center justify-center text-white mb-6 relative']">
                            <span class="text-4xl font-bold">{{ step.number }}</span>
                            <div v-if="step.number < 4" class="absolute left-full top-1/2 w-full h-0.5 bg-gray-300 -translate-y-1/2"></div>
                        </div>
                        <h3 class="text-xl font-bold text-[#0a2847] mb-4">{{ step.title }}</h3>
                        <p class="text-sm text-gray-600">{{ step.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Countdown & Events Section -->
        <section class="bg-cover bg-center py-20" style="background-image: url('/demo/images/school/1.jpg')">
            <div class="bg-white/95 py-16">
                <div class="container mx-auto px-6 text-center mb-16">
                    <div class="flex justify-center gap-12 mb-8">
                        <div>
                            <div class="text-6xl font-bold text-[#0a2847]">{{ String(countdown.days).padStart(2, '0') }}</div>
                            <div class="text-gray-600 mt-2">Days</div>
                        </div>
                        <div>
                            <div class="text-6xl font-bold text-[#0a2847]">{{ String(countdown.hours).padStart(2, '0') }}</div>
                            <div class="text-gray-600 mt-2">Hours</div>
                        </div>
                        <div>
                            <div class="text-6xl font-bold text-[#0a2847]">{{ String(countdown.minutes).padStart(2, '0') }}</div>
                            <div class="text-gray-600 mt-2">Minutes</div>
                        </div>
                        <div>
                            <div class="text-6xl font-bold text-[#0a2847]">{{ String(countdown.seconds).padStart(2, '0') }}</div>
                            <div class="text-gray-600 mt-2">Seconds</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- First School Day CTA -->
        <section class="bg-[#0a2847] text-white py-12">
            <div class="container mx-auto px-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-6">
                        <div class="bg-white rounded-full p-6">
                            <i class="pi pi-flag text-orange-500 text-4xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold mb-2">NEW ACADEMIC YEAR</h3>
                            <p class="text-gray-300">Prepare your child for an exciting journey of learning and discovery</p>
                        </div>
                    </div>
                    <button class="border-2 border-white text-white px-8 py-3 rounded-full font-semibold hover:bg-white hover:text-[#0a2847] transition-colors">
                        GET READY
                    </button>
                </div>
            </div>
        </section>

        <!-- Events Section -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-[#0a2847] mb-4">UPCOMING EVENTS</h2>
                    <p class="text-gray-600">Stay connected with school activities and celebrations</p>
                    <div class="w-16 h-1 bg-orange-500 mx-auto mt-4"></div>
                </div>
            </div>
        </section>

        <!-- Take First Step CTA -->
        <section class="bg-gray-50 py-12">
            <div class="container mx-auto px-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-6">
                        <div class="bg-white rounded-full p-6 border-2 border-orange-500">
                            <i class="pi pi-send text-orange-500 text-4xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-[#0a2847] mb-2">START YOUR JOURNEY</h3>
                            <p class="text-gray-600">Give your child the gift of quality education at Mozambique's premier primary school</p>
                        </div>
                    </div>
                    <button class="border-2 border-orange-500 text-orange-500 px-8 py-3 rounded-full font-semibold hover:bg-orange-500 hover:text-white transition-colors">
                        CONTACT US
                    </button>
                </div>
            </div>
        </section>

        <!-- What's New / Blog Section -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-[#0a2847] mb-4">WHAT'S NEW</h2>
                    <p class="text-gray-600">Keep up to date with the latest news</p>
                    <div class="w-16 h-1 bg-orange-500 mx-auto mt-4"></div>
                </div>
                <div class="grid grid-cols-3 gap-8 max-w-6xl mx-auto">
                    <div v-for="post in blogPosts" :key="post.title" class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-xl transition-shadow">
                        <div class="aspect-[4/3] bg-cover bg-center" :style="`background-image: url('${post.image}')`"></div>
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="text-4xl font-bold text-orange-500">{{ post.date }}</span>
                                <span class="text-sm text-gray-500">/ {{ post.month }}</span>
                            </div>
                            <h3 class="text-xl font-bold text-[#0a2847] mb-3">{{ post.title }}</h3>
                            <p class="text-gray-600 text-sm">{{ post.excerpt }}</p>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-12">
                    <button class="border-2 border-orange-500 text-orange-500 px-8 py-3 rounded-full font-semibold hover:bg-orange-500 hover:text-white transition-colors">
                        GO TO BLOG
                    </button>
                </div>
            </div>
        </section>

        <!-- Newsletter Section -->
        <section class="bg-gray-50 py-16">
            <div class="container mx-auto px-6">
                <div class="text-center">
                    <div class="bg-white rounded-full w-20 h-20 mx-auto mb-6 flex items-center justify-center shadow-md">
                        <i class="pi pi-envelope text-orange-500 text-3xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-[#0a2847] mb-6">Want to Hear From Us?</h2>
                    <div class="flex gap-4 max-w-md mx-auto">
                        <input 
                            type="email" 
                            placeholder="Your email address" 
                            class="flex-1 px-6 py-3 rounded-full border border-gray-300 focus:outline-none focus:border-orange-500"
                        />
                        <button class="bg-orange-500 hover:bg-orange-600 text-white px-8 py-3 rounded-full font-semibold transition-colors">
                            SIGN UP
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-[#0a2847] text-white pt-16 pb-8">
            <div class="container mx-auto px-6">
                <div class="grid grid-cols-3 gap-12 mb-12">
                    <!-- About Us -->
                    <div>
                        <h3 class="text-xl font-bold mb-6 pb-3 border-b-2 border-orange-500 inline-block">ABOUT US</h3>
                        <p class="text-gray-300 mb-6">
                            Prestige Primary School has been Mozambique's leader in quality primary education since 2011. We provide a nurturing environment where children develop academically, socially, and emotionally. Our bilingual program, experienced teachers, and modern facilities ensure every child reaches their full potential while maintaining strong ties to Mozambican culture and values.
                        </p>
                        <div class="flex items-center gap-2 mb-4">
                            <h2 class="text-2xl font-bold">Prestige<span class="text-orange-500">.</span></h2>
                            <span class="text-xs text-gray-400">primary school</span>
                        </div>
                    </div>

                    <!-- Recent Posts -->
                    <div>
                        <h3 class="text-xl font-bold mb-6 pb-3 border-b-2 border-orange-500 inline-block">RECENT POSTS</h3>
                        <div class="space-y-4">
                            <div v-for="post in blogPosts" :key="post.title" class="flex gap-4">
                                <div class="w-16 h-16 bg-cover bg-center rounded flex-shrink-0" :style="`background-image: url('${post.image}')`"></div>
                                <div>
                                    <h4 class="font-semibold text-sm mb-1 hover:text-orange-500 cursor-pointer">{{ post.title }}</h4>
                                    <div class="flex items-center gap-2">
                                        <span class="text-2xl font-bold text-orange-500">{{ post.date }}</span>
                                        <span class="text-xs text-gray-400">/ {{ post.month }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Our Features -->
                    <div>
                        <h3 class="text-xl font-bold mb-6 pb-3 border-b-2 border-orange-500 inline-block">WHY CHOOSE US</h3>
                        <p class="text-gray-300 mb-6">
                            Prestige School stands out as Mozambique's premier primary education institution, offering world-class facilities and comprehensive programs.
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-center gap-3">
                                <i class="pi pi-check text-orange-500"></i>
                                <span class="text-gray-300">Bilingual education (Portuguese & English)</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="pi pi-check text-orange-500"></i>
                                <span class="text-gray-300">Qualified international teaching staff</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="pi pi-check text-orange-500"></i>
                                <span class="text-gray-300">Modern air-conditioned classrooms</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="pi pi-check text-orange-500"></i>
                                <span class="text-gray-300">Advanced STEM laboratory</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="pi pi-check text-orange-500"></i>
                                <span class="text-gray-300">Sports facilities and programs</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="pi pi-check text-orange-500"></i>
                                <span class="text-gray-300">Nutritious meal program</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="pi pi-check text-orange-500"></i>
                                <span class="text-gray-300">Safe transportation service</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Social Media & Copyright -->
                <div class="border-t border-gray-700 pt-8">
                    <div class="flex justify-center gap-6 mb-6">
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-orange-500 transition-colors">
                            <i class="pi pi-facebook"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-orange-500 transition-colors">
                            <i class="pi pi-youtube"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-orange-500 transition-colors">
                            <i class="pi pi-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-orange-500 transition-colors">
                            <i class="pi pi-linkedin"></i>
                        </a>
                    </div>
                    <div class="text-center text-gray-400 text-sm">
                        © 2026 Prestige Primary School - Excellence in Education | Maputo, Mozambique
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Custom styles if needed */
</style>
