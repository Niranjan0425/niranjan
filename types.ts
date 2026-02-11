
export interface Education {
  institution: string;
  location: string;
  degree: string;
  period: string;
  score?: string;
}

export interface Skill {
  name: string;
  level: 'Skillful' | 'Experienced';
}

export interface Project {
  title: string;
  period: string;
  description: string;
  features: string[];
  link: string;
}

export interface Language {
  name: string;
  proficiency: string;
}
