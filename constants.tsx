
import { Education, Skill, Project, Language } from './types';

export const PERSONAL_INFO = {
  name: "Niranjan",
  title: "College Student",
  location: "Coonoor, India",
  phone: "9025255617",
  email: "niranjanindhul704@gmail.com",
  summary: "Highly adaptable individual with strong active listening skills and emerging leadership potential. Possesses foundational knowledge in UI/UX design and demonstrates creative thinking. Eager to leverage these abilities for academic success and collaborative learning experiences."
};

export const EDUCATION: Education[] = [
  {
    institution: "Sankara College of Science and Commerce",
    location: "Coimbatore",
    degree: "Bachelor of Computer Science and Data Analytics",
    period: "07/2024 - Present"
  },
  {
    institution: "St Antony's Hr Sec School",
    location: "Coonoor",
    degree: "HSC",
    period: "06/2023 - 04/2024",
    score: "66%"
  },
  {
    institution: "St Joseph Boys Anglo-Indian Hr.Sec School",
    location: "Coonoor",
    degree: "SSLC",
    period: "12/2021 - 04/2022",
    score: "60%"
  }
];

export const SKILLS: Skill[] = [
  { name: "Communication", level: "Skillful" },
  { name: "Adaptability", level: "Skillful" },
  { name: "Time Management", level: "Skillful" },
  { name: "Teamwork", level: "Experienced" },
  { name: "AI Tools", level: "Skillful" }
];

export const PROJECTS: Project[] = [
  {
    title: "Mozhi-Communication Application",
    period: "11/2025 - 12/2025",
    description: "AI-powered communication chatbot for improving English fluency. Developed to enable students to improve fluency through text and voice interactions from Tamil and Tanglish.",
    features: ["NLP Integration", "Real-time translation", "Contextual responses", "Voice interactions"],
    link: "https://mozhi-ai-translation-chatbot-950441171205.us-west1.run.app/"
  },
  {
    title: "AI Chatbot (Sankara Connect)",
    period: "11/2025 - 11/2025",
    description: "College information chatbot with voice assistance. Designed for college to enhance the experience for students and parents to get details easily.",
    features: ["Bilingual support (Tamil and English)", "Voice assistance", "Automations", "User-friendly interface"],
    link: "https://sankaraconnect-950441171205.us-west1.run.app"
  }
];

export const LANGUAGES: Language[] = [
  { name: "Tamil", proficiency: "Native Speaker" },
  { name: "English", proficiency: "Fluent" }
];
