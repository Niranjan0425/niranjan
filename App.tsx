import React, { useRef, useState, useEffect } from 'react';
import { motion, useScroll, useTransform, useSpring, Variants } from 'framer-motion';
import { 
  Mail, 
  Phone, 
  MapPin, 
  ExternalLink, 
  Code2, 
  Cpu, 
  GraduationCap, 
  ChevronDown,
  Sparkles,
  User,
  Rocket,
  Lightbulb,
  Heart,
  Target,
  Layers,
  Award,
  Crown
} from 'lucide-react';
import { PERSONAL_INFO, EDUCATION, SKILLS, PROJECTS } from './constants';

const MouseFollowingBot = () => {
  const [mousePos, setMousePos] = useState({ x: 0, y: 0 });
  
  useEffect(() => {
    const handleMouseMove = (e: MouseEvent) => {
      setMousePos({ x: e.clientX, y: e.clientY });
    };
    window.addEventListener('mousemove', handleMouseMove);
    return () => window.removeEventListener('mousemove', handleMouseMove);
  }, []);

  const getEyeMovement = (eyeRef: React.RefObject<HTMLDivElement>) => {
    if (!eyeRef.current) return { x: 0, y: 0 };
    const rect = eyeRef.current.getBoundingClientRect();
    const eyeCenterX = rect.left + rect.width / 2;
    const eyeCenterY = rect.top + rect.height / 2;
    
    const angle = Math.atan2(mousePos.y - eyeCenterY, mousePos.x - eyeCenterX);
    const distance = Math.min(6, Math.hypot(mousePos.x - eyeCenterX, mousePos.y - eyeCenterY) / 50);
    
    return {
      x: Math.cos(angle) * distance,
      y: Math.sin(angle) * distance
    };
  };

  const leftEyeRef = useRef<HTMLDivElement>(null);
  const rightEyeRef = useRef<HTMLDivElement>(null);

  const leftEyeMove = getEyeMovement(leftEyeRef);
  const rightEyeMove = getEyeMovement(rightEyeRef);

  return (
    <motion.div 
      initial={{ opacity: 0, y: 60 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 1.2, delay: 0.2, ease: "easeOut" }}
      className="relative flex flex-col items-center justify-center pointer-events-none mt-16 mb-8"
    >
      {/* Bot Head with Antenna */}
      <motion.div
        animate={{ y: [0, -10, 0], rotate: [0, 1, 0] }}
        transition={{ duration: 5, repeat: Infinity, ease: "easeInOut" }}
        className="relative z-10 w-28 h-28 bg-gradient-to-br from-amber-500/10 via-slate-900 to-amber-900/10 border-2 border-amber-500/30 rounded-[2.5rem] shadow-[0_0_50px_rgba(212,175,55,0.15)] backdrop-blur-md flex flex-col items-center justify-center p-4 overflow-hidden"
      >
        {/* Antenna */}
        <div className="absolute -top-4 left-1/2 -translate-x-1/2 w-1 h-6 bg-gradient-to-t from-amber-500 to-transparent">
          <motion.div 
            animate={{ opacity: [0.3, 1, 0.3], scale: [1, 1.2, 1] }}
            transition={{ duration: 2, repeat: Infinity }}
            className="w-2.5 h-2.5 bg-amber-400 rounded-full -top-1 -left-[3px] absolute shadow-[0_0_12px_#fbbf24]"
          />
        </div>

        {/* Face Screen */}
        <div className="w-full h-16 bg-slate-950/90 rounded-2xl border border-amber-500/20 flex items-center justify-center gap-4 relative shadow-inner overflow-hidden">
          {/* Scanline Effect */}
          <div className="absolute inset-0 bg-gradient-to-b from-transparent via-amber-500/5 to-transparent h-2 w-full animate-pulse opacity-20" />
          
          <div ref={leftEyeRef} className="w-6 h-6 bg-amber-500/5 rounded-full border border-amber-500/20 flex items-center justify-center relative">
            <motion.div 
              animate={{ x: leftEyeMove.x, y: leftEyeMove.y }}
              className="w-3 h-3 bg-amber-400 rounded-full shadow-[0_0_10px_#fbbf24]" 
            />
          </div>
          <div ref={rightEyeRef} className="w-6 h-6 bg-amber-500/5 rounded-full border border-amber-500/20 flex items-center justify-center relative">
            <motion.div 
              animate={{ x: rightEyeMove.x, y: rightEyeMove.y }}
              className="w-3 h-3 bg-amber-400 rounded-full shadow-[0_0_10px_#fbbf24]" 
            />
          </div>
          <div className="absolute bottom-2 w-10 h-[2px] bg-amber-500/20 rounded-full" />
        </div>
      </motion.div>

      {/* Bot Neck */}
      <div className="w-3 h-4 bg-gradient-to-b from-slate-800 to-slate-950 border-x border-amber-500/10" />

      {/* Bot Base/Shoulders (Structure) */}
      <div className="relative w-40 h-8">
        <div className="absolute inset-0 bg-gradient-to-r from-transparent via-amber-500/20 to-transparent blur-xl opacity-50" />
        <div className="w-full h-full bg-slate-900/80 backdrop-blur-md border border-amber-500/20 rounded-full flex items-center justify-center shadow-xl">
          <div className="w-[85%] h-[2px] bg-gradient-to-r from-transparent via-amber-500/40 to-transparent" />
        </div>
      </div>

      {/* Floor Shadow */}
      <motion.div 
        animate={{ scale: [1, 0.8, 1], opacity: [0.3, 0.1, 0.3] }}
        transition={{ duration: 5, repeat: Infinity, ease: "easeInOut" }}
        className="w-24 h-4 bg-amber-500/10 blur-md rounded-full mt-4"
      />
    </motion.div>
  );
};

const Floating3DElement = ({ delay = 0, initialX = 0, initialY = 0, size = 100, rotateSpeed = 20, colorClass = "amber" }) => (
  <motion.div
    className="absolute pointer-events-none opacity-10"
    style={{ 
      left: `${initialX}%`, 
      top: `${initialY}%`,
      width: size,
      height: size,
      perspective: 1000
    }}
    animate={{
      y: [0, -40, 0],
      rotateX: [0, 360],
      rotateY: [0, 360],
    }}
    transition={{
      duration: rotateSpeed,
      repeat: Infinity,
      ease: "linear",
      delay
    }}
  >
    <div className={`w-full h-full border border-${colorClass}-500/30 bg-${colorClass}-500/5 backdrop-blur-sm rounded-lg shadow-[0_0_30px_rgba(212,175,55,0.1)]`} />
  </motion.div>
);

const TiltCard = ({ children, className = "", borderClass = "border-amber-500/30" }) => {
  const x = useSpring(0, { stiffness: 300, damping: 30 });
  const y = useSpring(0, { stiffness: 300, damping: 30 });

  function handleMouse(event: React.MouseEvent<HTMLDivElement>) {
    const rect = event.currentTarget.getBoundingClientRect();
    const width = rect.width;
    const height = rect.height;
    const mouseX = event.clientX - rect.left;
    const mouseY = event.clientY - rect.top;
    const xPct = (mouseX / width - 0.5) * 20;
    const yPct = (mouseY / height - 0.5) * -20;
    x.set(xPct);
    y.set(yPct);
  }

  function handleMouseLeave() {
    x.set(0);
    y.set(0);
  }

  return (
    <motion.div
      onMouseMove={handleMouse}
      onMouseLeave={handleMouseLeave}
      style={{ rotateY: x, rotateX: y, transformStyle: "preserve-3d" }}
      className={`relative ${className} border ${borderClass} rounded-[2rem] overflow-hidden`}
    >
      <div style={{ transform: "translateZ(20px)" }}>
        {children}
      </div>
    </motion.div>
  );
};

const Navbar = () => (
  <nav className="fixed top-0 left-0 right-0 z-50 bg-black/80 backdrop-blur-xl border-b border-amber-900/30">
    <div className="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
      <motion.span 
        initial={{ opacity: 0, x: -20 }}
        animate={{ opacity: 1, x: 0 }}
        className="text-xl font-bold font-outfit text-amber-500 cursor-pointer flex items-center gap-2"
        onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
      >
        <div className="w-8 h-8 rounded-full bg-amber-500/20 flex items-center justify-center border border-amber-500/40 shadow-[0_0_15px_rgba(212,175,55,0.2)]">
           <Crown className="w-4 h-4 text-amber-400" />
        </div>
        {PERSONAL_INFO.name}
      </motion.span>
    </div>
  </nav>
);

const SectionHeader = ({ icon: Icon, title, color = "amber" }: { icon: any, title: string, color?: string }) => (
  <div className="flex items-center gap-4 mb-12">
    <div className={`p-3 rounded-2xl bg-${color}-500/10 border border-${color}-500/20 shadow-[0_0_20px_rgba(212,175,55,0.1)]`}>
      <Icon className={`w-6 h-6 text-${color}-400`} />
    </div>
    <h2 className="text-3xl font-outfit font-bold tracking-tight text-slate-100">{title}</h2>
  </div>
);

const App: React.FC = () => {
  const containerRef = useRef(null);
  const { scrollYProgress } = useScroll({
    target: containerRef,
    offset: ["start start", "end end"]
  });

  const heroY = useTransform(scrollYProgress, [0, 0.2], [0, -100]);
  const heroOpacity = useTransform(scrollYProgress, [0, 0.15], [1, 0]);
  const heroScale = useTransform(scrollYProgress, [0, 0.2], [1, 0.9]);

  const nameContainerVariants: Variants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: 0.1,
        delayChildren: 0.8
      }
    }
  };

  const letterVariants: Variants = {
    hidden: { opacity: 0, y: 50, scale: 0.8 },
    visible: {
      opacity: 1,
      y: 0,
      scale: 1,
      transition: {
        type: "spring",
        damping: 12,
        stiffness: 200
      }
    }
  };

  return (
    <div ref={containerRef} className="min-h-screen relative overflow-x-hidden bg-[#050505] selection:bg-amber-500/30 text-slate-200">
      <Navbar />

      <div className="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <Floating3DElement initialX={10} initialY={20} size={150} rotateSpeed={25} colorClass="amber" />
        <Floating3DElement initialX={85} initialY={15} size={100} rotateSpeed={30} colorClass="slate" />
        <Floating3DElement initialX={70} initialY={75} size={120} rotateSpeed={20} colorClass="amber" />
        <Floating3DElement initialX={8} initialY={85} size={80} rotateSpeed={35} colorClass="slate" />
        <div className="absolute top-[-15%] left-[-15%] w-[60%] h-[60%] bg-amber-900/10 blur-[150px] rounded-full" />
        <div className="absolute bottom-[-15%] right-[-15%] w-[60%] h-[60%] bg-slate-400/5 blur-[150px] rounded-full" />
      </div>
      
      <main className="max-w-6xl mx-auto px-6 relative z-10">
        <section className="min-h-screen flex flex-col justify-center items-center relative pt-16">
          <motion.div 
            style={{ y: heroY, opacity: heroOpacity, scale: heroScale, perspective: 1000 }}
            className="space-y-4 flex flex-col items-center text-center"
          >
            <MouseFollowingBot />

            <motion.div
              initial={{ opacity: 0, y: 40 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8, delay: 0.4, ease: "easeOut" }}
              className="flex flex-col items-center"
            >
              <div className="relative group">
                <div className="absolute -inset-1 bg-gradient-to-r from-amber-500 via-amber-200 to-amber-700 rounded-full blur opacity-15 group-hover:opacity-40 transition duration-1000" />
                <span className="relative px-5 py-2 text-xs font-black tracking-[0.4em] text-amber-500 uppercase bg-black/50 rounded-full border border-amber-500/40 backdrop-blur-md">
                  CSDA Student
                </span>
              </div>
              
              <motion.h1 
                variants={nameContainerVariants}
                initial="hidden"
                animate="visible"
                className="mt-10 text-8xl md:text-[10rem] font-outfit font-black tracking-tighter leading-none flex overflow-hidden"
              >
                {PERSONAL_INFO.name.split("").map((letter, index) => (
                  <motion.span
                    key={index}
                    variants={letterVariants}
                    className="text-transparent bg-clip-text bg-gradient-to-b from-amber-100 via-amber-400 to-amber-700 drop-shadow-[0_15px_15px_rgba(212,175,55,0.2)]"
                  >
                    {letter}
                  </motion.span>
                ))}
              </motion.h1>

              <div className="mt-4 flex items-center gap-2 text-slate-400 font-bold uppercase tracking-[0.3em] text-[10px]">
                <div className="w-12 h-[1px] bg-amber-500/50" />
                Portfolio
                <div className="w-12 h-[1px] bg-amber-500/50" />
              </div>
            </motion.div>

            <div className="flex flex-col items-center gap-10 max-w-2xl mt-4">
              <motion.p
                initial={{ opacity: 0, y: 30 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.8, delay: 0.6, ease: "easeOut" }}
                className="text-lg md:text-xl text-slate-400 leading-relaxed font-medium"
              >
                {PERSONAL_INFO.summary}
              </motion.p>

              <motion.div
                initial={{ opacity: 0, scale: 0.8 }}
                animate={{ opacity: 1, scale: 1, y: [0, 10, 0] }}
                transition={{ 
                  opacity: { duration: 0.8, delay: 1 },
                  scale: { duration: 0.8, delay: 1 },
                  y: { duration: 2, repeat: Infinity, ease: "easeInOut" }
                }}
                className="flex flex-col items-center gap-3 cursor-pointer group"
                onClick={() => document.getElementById('about')?.scrollIntoView({ behavior: 'smooth' })}
              >
                <span className="text-[10px] uppercase tracking-[0.5em] font-black text-amber-500/60 group-hover:text-amber-400 transition-colors">SCROLL</span>
                <div className="w-10 h-10 rounded-full border border-amber-500/20 bg-amber-500/5 flex items-center justify-center group-hover:border-amber-500/50 group-hover:bg-amber-500/10 transition-all shadow-lg">
                  <ChevronDown className="w-5 h-5 text-amber-500" />
                </div>
              </motion.div>
            </div>
          </motion.div>
        </section>

        <section id="about" className="py-32 scroll-mt-20">
          <motion.div
            initial={{ opacity: 0, y: 50 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            className="space-y-16"
          >
            <SectionHeader icon={User} title="Legacy & Vision" />
            
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-10">
              <div className="lg:col-span-8 space-y-10">
                <p className="text-3xl md:text-4xl text-slate-200 leading-[1.1] font-outfit font-black tracking-tight">
                  I'm <span className="text-amber-500 relative inline-block">{PERSONAL_INFO.name}<span className="absolute bottom-1 left-0 w-full h-[6px] bg-amber-500/10 -z-10" /></span>, architecting intelligence through <span className="text-slate-300 italic">Data Science</span>.
                </p>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                  {[
                    { icon: Rocket, title: "The Pursuit", color: "amber", text: "Transforming raw curiosity into high-performance AI systems that simplify complex communication." },
                    { icon: Target, title: "Precision Focus", color: "slate", text: "Delivering accessible, elite technology solutions that empower and redefine the student experience." },
                    { icon: Heart, title: "Craftsmanship", color: "amber", text: "Merging technical excellence with empathetic design, creating a seamless fusion of code and user experience." },
                    { icon: Lightbulb, title: "Innovation", color: "slate", text: "Collaborating with elite teams to solve intricate challenges at the forefront of the digital revolution." }
                  ].map((card, i) => (
                    <TiltCard key={i} borderClass={card.color === 'amber' ? 'border-amber-500/20' : 'border-slate-500/20'}>
                      <div className="h-full p-10 bg-gradient-to-br from-slate-900/80 to-black/80 backdrop-blur-md shadow-2xl group transition-all">
                        <div className={`w-14 h-14 rounded-2xl bg-${card.color}-500/5 border border-${card.color}-500/20 flex items-center justify-center mb-8 group-hover:scale-110 transition-transform shadow-[0_0_20px_rgba(212,175,55,0.1)]`}>
                          <card.icon className={`w-7 h-7 text-${card.color}-400`} />
                        </div>
                        <h3 className="text-xl font-outfit font-black mb-4 tracking-tight text-white">{card.title}</h3>
                        <p className="text-slate-400 text-sm leading-relaxed font-medium">{card.text}</p>
                      </div>
                    </TiltCard>
                  ))}
                </div>
              </div>

              <div className="lg:col-span-4 space-y-8">
                <motion.div 
                  whileHover={{ scale: 1.02 }}
                  className="p-10 bg-gradient-to-br from-amber-600/20 via-black to-black border border-amber-500/30 rounded-[3rem] shadow-[0_20px_40px_rgba(0,0,0,0.4)] relative overflow-hidden group"
                >
                  <div className="absolute top-0 right-0 w-40 h-40 bg-amber-500/10 blur-[80px] group-hover:bg-amber-500/25 transition-all" />
                  <Award className="w-10 h-10 text-amber-500 mb-8" />
                  <h3 className="text-2xl font-outfit font-black mb-6 leading-tight text-white">Forging Premier Digital Experiences.</h3>
                  <p className="text-slate-400 text-sm leading-relaxed mb-10 font-medium">
                    Designing the next generation of AI-driven interfaces. Let's elevate your next project.
                  </p>
                  <motion.button 
                    onClick={() => document.getElementById('contact')?.scrollIntoView({ behavior: 'smooth' })}
                    whileHover={{ scale: 1.05, boxShadow: "0 0 30px rgba(212,175,55,0.4)" }}
                    whileTap={{ scale: 0.95 }}
                    className="w-full py-5 bg-gradient-to-r from-amber-600 to-amber-400 text-black rounded-[1.5rem] font-black uppercase tracking-[0.2em] text-[10px] shadow-2xl transition-all flex items-center justify-center gap-2"
                  >
                    Initiate Contact <ExternalLink className="w-4 h-4" />
                  </motion.button>
                </motion.div>

                <div className="p-8 bg-black/60 border border-slate-800 rounded-[2rem] shadow-inner backdrop-blur-md">
                  <h4 className="text-[10px] font-black uppercase tracking-[0.4em] text-amber-500 mb-8">Strategic Expertise</h4>
                  <div className="flex flex-wrap gap-3">
                    {["Deep Learning", "NLP Core", "Data Ethics", "Ux Strategy", "Predictive Modeling"].map((tag) => (
                      <span key={tag} className="text-[10px] font-bold uppercase tracking-widest px-4 py-2 bg-amber-500/5 text-amber-200 rounded-full border border-amber-500/10">
                        {tag}
                      </span>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </motion.div>
        </section>

        <section id="education" className="py-32 scroll-mt-20">
          <motion.div
            initial={{ opacity: 0, y: 50 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            className="space-y-16"
          >
            <SectionHeader icon={GraduationCap} title="Academic Excellence" />

            <div className="space-y-8">
              {EDUCATION.map((edu, idx) => (
                <motion.div
                  key={idx}
                  initial={{ opacity: 0, x: -30 }}
                  whileInView={{ opacity: 1, x: 0 }}
                  transition={{ delay: idx * 0.1 }}
                  className="group relative p-10 bg-slate-900/10 border border-amber-900/20 rounded-[2.5rem] hover:border-amber-500/40 transition-all hover:bg-amber-900/5 shadow-lg backdrop-blur-sm"
                >
                  <div className="flex flex-col md:flex-row md:items-center justify-between gap-8">
                    <div className="space-y-3">
                      <span className="inline-block text-[10px] font-black text-amber-500 uppercase tracking-[0.3em] mb-2 px-3 py-1 bg-amber-500/5 rounded-full border border-amber-500/10">{edu.period}</span>
                      <h3 className="text-2xl md:text-3xl font-black font-outfit text-white group-hover:text-amber-200 transition-colors leading-tight">{edu.institution}</h3>
                      <p className="text-slate-400 font-bold text-lg tracking-tight">{edu.degree}</p>
                    </div>
                    <div className="flex flex-col items-end gap-4">
                       <div className="flex items-center gap-2 text-slate-500 text-sm font-bold">
                        <MapPin className="w-4 h-4 text-amber-500/40" /> {edu.location}
                      </div>
                      {edu.score && (
                        <div className="px-5 py-2 bg-amber-500/10 text-amber-400 rounded-2xl border border-amber-500/20 text-sm font-black shadow-[0_0_15px_rgba(212,175,55,0.1)]">
                          SCORE: {edu.score}
                        </div>
                      )}
                    </div>
                  </div>
                </motion.div>
              ))}
            </div>
          </motion.div>
        </section>

        <section id="skills" className="py-32 scroll-mt-20">
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            whileInView={{ opacity: 1, scale: 1 }}
            viewport={{ once: true }}
            className="space-y-16"
          >
            <SectionHeader icon={Cpu} title="Technological Mastery" color="slate" />

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
              {SKILLS.map((skill, idx) => (
                <TiltCard key={idx} borderClass="border-amber-900/20">
                  <div className="p-10 bg-black/60 border border-slate-800/50 rounded-[2.5rem] flex flex-col justify-between group hover:border-amber-500/50 transition-all shadow-2xl backdrop-blur-lg">
                    <div className="flex items-center justify-between mb-10">
                      <span className="text-xl font-black font-outfit text-white group-hover:text-amber-400 transition-colors tracking-tight">{skill.name}</span>
                      <div className="w-12 h-12 rounded-2xl bg-slate-900 flex items-center justify-center border border-amber-500/10 group-hover:border-amber-500/40 shadow-inner">
                        <Sparkles className="w-5 h-5 text-amber-500/20 group-hover:text-amber-400 transition-colors" />
                      </div>
                    </div>
                    <div className="space-y-5">
                      <div className="flex items-center justify-between text-[10px] text-slate-500 uppercase tracking-[0.4em] font-black">
                        <span>Proficiency</span>
                        <span className="text-amber-500">{skill.level}</span>
                      </div>
                      <div className="h-2.5 w-full bg-slate-900 rounded-full overflow-hidden p-[2px] shadow-inner border border-white/5">
                        <motion.div 
                          initial={{ width: 0 }}
                          whileInView={{ width: skill.level === 'Skillful' ? '90%' : '75%' }}
                          transition={{ duration: 1.5, ease: "easeOut" }}
                          className="h-full bg-gradient-to-r from-amber-600 via-amber-400 to-slate-200 rounded-full"
                        />
                      </div>
                    </div>
                  </div>
                </TiltCard>
              ))}
            </div>
          </motion.div>
        </section>

        <section id="projects" className="py-32 scroll-mt-20">
          <motion.div
            initial={{ opacity: 0, y: 50 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            className="space-y-16"
          >
            <SectionHeader icon={Code2} title="Premier Projects" />

            <div className="grid gap-16">
              {PROJECTS.map((project, idx) => (
                <motion.div
                  key={idx}
                  initial={{ opacity: 0, scale: 0.95 }}
                  whileInView={{ opacity: 1, scale: 1 }}
                  transition={{ delay: idx * 0.1 }}
                  className="group relative p-12 md:p-16 bg-gradient-to-br from-slate-900/40 to-black/80 border border-amber-900/30 rounded-[4rem] hover:border-amber-500/30 transition-all overflow-hidden shadow-[0_30px_60px_rgba(0,0,0,0.5)]"
                >
                  <div className="absolute -top-32 -right-32 w-80 h-80 bg-amber-500/5 blur-[120px] group-hover:bg-amber-500/15 transition-all rounded-full" />
                  
                  <div className="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
                    <div className="lg:col-span-8 space-y-8">
                      <div className="space-y-3">
                        <span className="text-[10px] font-black text-amber-500 uppercase tracking-[0.4em]">{project.period}</span>
                        <h3 className="text-4xl md:text-5xl font-black font-outfit text-white tracking-tight leading-none">{project.title}</h3>
                      </div>
                      <p className="text-slate-400 text-xl leading-relaxed max-w-2xl font-medium">{project.description}</p>
                      <div className="flex flex-wrap gap-3 pt-4">
                        {project.features.map((feature, fIdx) => (
                          <span key={fIdx} className="text-[10px] font-black uppercase tracking-[0.2em] px-5 py-2.5 bg-black/60 text-slate-300 rounded-2xl border border-amber-500/10 shadow-lg group-hover:border-amber-500/30 transition-colors">
                            {feature}
                          </span>
                        ))}
                      </div>
                    </div>
                    <div className="lg:col-span-4 flex justify-start lg:justify-end pt-4">
                      <motion.a
                        href={project.link}
                        target="_blank"
                        rel="noopener noreferrer"
                        whileHover={{ scale: 1.05, y: -5, boxShadow: "0 10px 30px rgba(212,175,55,0.3)" }}
                        whileTap={{ scale: 0.95 }}
                        className="group/btn flex items-center justify-center gap-4 px-12 py-6 bg-gradient-to-br from-amber-500 to-amber-700 text-black rounded-[2rem] font-black uppercase tracking-[0.3em] text-[10px] shadow-2xl transition-all"
                      >
                        EXPLORE <ExternalLink className="w-4 h-4 group-hover/btn:translate-x-1 group-hover/btn:-translate-y-1 transition-transform" />
                      </motion.a>
                    </div>
                  </div>
                </motion.div>
              ))}
            </div>
          </motion.div>
        </section>

        <section id="contact" className="py-40 pb-60 scroll-mt-20">
          <motion.div
            initial={{ opacity: 0, y: 50 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            className="text-center space-y-24"
          >
            <div className="space-y-8">
              <h2 className="text-6xl md:text-8xl font-outfit font-black tracking-tighter text-white">Let's <span className="text-transparent bg-clip-text bg-gradient-to-r from-amber-200 via-amber-500 to-amber-800">Communicate.</span></h2>
              <p className="text-slate-400 max-w-xl mx-auto text-xl font-medium">
                From vision to reality — one powerful idea at a time.
              </p>
            </div>

            <div className="grid md:grid-cols-2 gap-10 max-w-4xl mx-auto">
              <TiltCard borderClass="border-amber-500/10">
                <motion.a
                  href={`mailto:${PERSONAL_INFO.email}`}
                  className="block p-14 bg-gradient-to-br from-slate-900/50 to-black border border-amber-900/20 rounded-[3.5rem] text-center space-y-8 hover:border-amber-500/50 hover:bg-black/80 transition-all shadow-[0_20px_50px_rgba(0,0,0,0.4)] group"
                >
                  <div className="w-20 h-20 bg-amber-500/5 rounded-[2rem] flex items-center justify-center mx-auto border border-amber-500/10 group-hover:scale-110 group-hover:border-amber-500/50 transition-all shadow-[0_0_20px_rgba(212,175,55,0.1)]">
                    <Mail className="w-10 h-10 text-amber-500" />
                  </div>
                  <div className="space-y-3">
                    <span className="text-[10px] font-black text-slate-500 uppercase tracking-[0.5em] mb-2 block">Direct Intelligence</span>
                    <p className="font-black text-2xl text-white tracking-tight group-hover:text-amber-300 transition-colors">{PERSONAL_INFO.email}</p>
                  </div>
                </motion.a>
              </TiltCard>

              <TiltCard borderClass="border-slate-500/10">
                <motion.a
                  href={`tel:${PERSONAL_INFO.phone}`}
                  className="block p-14 bg-gradient-to-br from-slate-900/50 to-black border border-slate-900/20 rounded-[3.5rem] text-center space-y-8 hover:border-slate-400/50 hover:bg-black/80 transition-all shadow-[0_20px_50px_rgba(0,0,0,0.4)] group"
                >
                  <div className="w-20 h-20 bg-slate-500/5 rounded-[2rem] flex items-center justify-center mx-auto border border-slate-500/10 group-hover:scale-110 group-hover:border-slate-400/50 transition-all">
                    <Phone className="w-10 h-10 text-slate-400" />
                  </div>
                  <div className="space-y-3">
                    <span className="text-[10px] font-black text-slate-500 uppercase tracking-[0.5em] mb-2 block">Voice Secure</span>
                    <p className="font-black text-2xl text-white tracking-tight group-hover:text-slate-200 transition-colors">{PERSONAL_INFO.phone}</p>
                  </div>
                </motion.a>
              </TiltCard>
            </div>
          </motion.div>
        </section>
      </main>

      <footer className="py-24 border-t border-amber-900/20 bg-black/90 text-center relative z-10">
        <div className="max-w-6xl mx-auto px-6 flex flex-col items-center gap-10">
          <div className="space-y-4">
            <span className="text-xs text-amber-500/60 font-black uppercase tracking-[0.6em] block">{PERSONAL_INFO.name} Portfolio</span>
          </div>
        </div>
      </footer>
    </div>
  );
};

export default App;