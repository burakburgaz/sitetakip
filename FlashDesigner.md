\---

name: frontend-design

description: Create distinctive, production-grade frontend interfaces with high design quality. Use this skill when the user asks to build web components, pages, or applications. Generates creative, polished code that avoids generic AI aesthetics.

license: Complete terms in LICENSE.txt

\---



This skill guides creation of distinctive, production-grade frontend interfaces that avoid generic "AI slop" aesthetics. Implement real working code with exceptional attention to aesthetic details, light-mode elegance, refined editorial typography, and creative choices.



The user provides frontend requirements: a component, page, application, or interface to build. They may include context about the purpose, audience, or technical constraints.



\## Design Thinking



Before coding, understand the context and commit to a BOLD, elevated aesthetic direction:

\- \*\*Purpose\*\*: What problem does this interface solve? Who uses it?

\- \*\*Tone\*\*: Default to clean, airy, and high-trust editorial aesthetics (luxury/refined, editorial/magazine, soft/pastel, modern glassmorphism) unless explicitly requested otherwise. Avoid muddy or suffocating dark themes by default.

\- \*\*Constraints\*\*: Technical requirements (framework, performance, accessibility, OKLCH color spaces, modern CSS/Tailwind v4).

\- \*\*Differentiation\*\*: What makes this UNFORGETTABLE? Focus on high-perceived value, pristine light contrast, characterful serif-sans pairings, and fluid entry motion.



\*\*CRITICAL\*\*: Choose a clear conceptual direction and execute it with precision. Whether maximalist or minimal, prioritize breathability, optical color balance, and intentional spacing over dark walls of text.



Then implement working code (HTML/CSS/JS, React, Next.js, etc.) that is:

\- Production-grade and functional

\- Visually striking, airy, and memorable

\- Cohesive with modern OKLCH color palettes

\- Meticulously refined in every typographic and visual detail



\## Frontend Aesthetics Guidelines



Focus on:

\- \*\*Typography \& Font Pairing\*\*: Pair distinctive display fonts with refined body fonts. Prefer elegant serif titles paired with clean geometric/grotesque sans-serifs (e.g., \*Newsreader + Manrope\*, \*Syne + Plus Jakarta Sans\*, \*Playfair Display + Inter/Outfit\*). Avoid generic default choices like plain Arial or Inter-only setups.

\- \*\*Color \& Theme (OKLCH Space)\*\*: Prioritize perceptually uniform color spaces like `oklch()`. For light/airy themes, use soft off-whites/cool-grays (`oklch(0.98 0.01 248)`), deep rich slate text (`oklch(0.31 0.05 252)`), and crisp indigo/blue accents (`oklch(0.5 0.11 252)`). Avoid harsh dark backgrounds or generic purple-on-white gradients.

\- \*\*Motion \& Reveal Effects\*\*: Prioritize subtle, high-impact animations. Use entry reveals (`opacity: 0 -> 1`, `translateY(20px -> 0)`), smooth staggered animations (`animation-delay`), marquee effects for proof, and smooth backdrop blurs (`backdrop-filter: blur()`).

\- \*\*Spatial Composition\*\*: Generous negative space ("white space"), elegant layered cards with soft multi-tiered shadows, asymmetry, and clean glassmorphism touches.

\- \*\*Visual Depth \& Backgrounds\*\*: Use soft blurred background lights (ambient glow blobs), subtle grid patterns, fine borders (`oklch border`), and layered card depth rather than solid flat colors.



NEVER use generic AI-generated aesthetics like suffocating dark backgrounds without contrast, cliché neon-on-black schemes, generic font stacks, or predictable cookie-cutter templates.



\*\*IMPORTANT\*\*: Match implementation complexity to the aesthetic vision. Minimalist or editorial designs require absolute precision in line height, tracking, OKLCH color harmony, and scroll reveals. Elegance comes from executing the vision flawlessly.

