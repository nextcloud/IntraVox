import { Extension } from '@tiptap/core';
import { Plugin, PluginKey } from '@tiptap/pm/state';

/**
 * Dummy Text Generator — Easter Egg for IntraVox Text Widget
 *
 * Type =dad(), =dadjokes(3,5), or =lorem(2,4) on an empty line
 * and press Enter to generate dummy content.
 *
 * Inspired by Microsoft Word's =rand(p,s) command.
 */

const dadJokes = [
	"I'm afraid for the calendar. Its days are numbered.",
	'Why do fathers take an extra pair of socks when they go golfing? In case they get a hole in one!',
	'What do you call a fake noodle? An impasta!',
	"I wouldn't buy anything with velcro. It's a total rip-off.",
	'What did the ocean say to the beach? Nothing, it just waved.',
	"Why don't eggs tell jokes? They'd crack each other up.",
	'I used to hate facial hair, but then it grew on me.',
	'What do you call a bear with no teeth? A gummy bear!',
	"I'm reading a book about anti-gravity. It's impossible to put down!",
	'Did you hear about the claustrophobic astronaut? He just needed a little space.',
	'Why did the scarecrow win an award? He was outstanding in his field!',
	"I told my wife she was drawing her eyebrows too high. She looked surprised.",
	"What do you call a dog that does magic tricks? A Labracadabrador.",
	"I used to play piano by ear, but now I use my hands.",
	"Why couldn't the bicycle stand up by itself? It was two tired!",
	"What did the grape do when he got stepped on? He let out a little wine.",
	"I got a job at a bakery because I kneaded dough.",
	"What do you call a sleeping dinosaur? A dino-snore!",
	"Why did the math book look so sad? Because of all of its problems.",
	"How do you organize a space party? You planet!",
	"What do you call cheese that isn't yours? Nacho cheese!",
	"Why did the coffee file a police report? It got mugged.",
	"I'm on a seafood diet. I see food and I eat it.",
	"What do you call a factory that makes okay products? A satisfactory!",
	"Why don't scientists trust atoms? Because they make up everything!",
	"What do you call a can opener that doesn't work? A can't opener!",
	"I told a chemistry joke, but I got no reaction.",
	"What did the janitor say when he jumped out of the closet? Supplies!",
	"Why did the golfer bring two pairs of pants? In case he got a hole in one!",
	"What do you call a fish without eyes? A fsh!",
	"I used to be a banker, but I lost interest.",
	"Why do chicken coops only have two doors? Because if they had four, they would be chicken sedans!",
	"What did one wall say to the other wall? I'll meet you at the corner!",
	"Why did the invisible man turn down the job offer? He couldn't see himself doing it.",
	"What do you call a boomerang that won't come back? A stick.",
	"I once got fired from a canned juice company. Apparently I couldn't concentrate.",
	"What do you call a belt made of watches? A waist of time!",
	"Why did the stadium get hot after the game? All of the fans left!",
	"How does a penguin build its house? Igloos it together!",
	"What did the buffalo say to his son when he left for school? Bison!",
	"I was going to tell a time-traveling joke, but you didn't like it.",
	"What do you call a group of disorganized cats? A cat-astrophe!",
	"Why do bees have sticky hair? Because they use honeycombs!",
	"What do you call a lazy kangaroo? A pouch potato!",
	"I'm so good at sleeping, I can do it with my eyes closed.",
	"What did the left eye say to the right eye? Between you and me, something smells.",
	"Why did the bicycle fall over? Because it was two tired!",
	"What do you call a pig that does karate? A pork chop!",
	"I used to be addicted to the hokey pokey, but I turned myself around.",
	"What do you call a snowman with a six-pack? An abdominal snowman!",
	"Why did the tomato turn red? Because it saw the salad dressing!",
	"I asked my dog what's two minus two. He said nothing.",
	"What do you call a cow with no legs? Ground beef!",
	"Why can't you hear a pterodactyl going to the bathroom? Because the P is silent!",
	"What do you call a deer with no eyes? No idea!",
	"I just watched a documentary about beavers. It was the best dam show I ever saw!",
	"Why don't skeletons fight each other? They don't have the guts!",
	"What do you call a computer that sings? A-Dell!",
	"I only know 25 letters of the alphabet. I don't know Y.",
	"What did the fish say when he swam into a wall? Dam!",
	"Why did the student eat his homework? Because his teacher told him it was a piece of cake!",
	"What do you call a sleeping bull? A bulldozer!",
	"I told my computer I needed a break, and now it won't stop sending me Kit Kat ads.",
	"What do you call a dinosaur that crashes their car? Tyrannosaurus Wrecks!",
	"Why did the banker switch careers? He lost interest.",
	"What do you call an alligator in a vest? An investigator!",
	"I'm terrified of elevators, so I'm going to start taking steps to avoid them.",
	"What kind of music do mummies listen to? Wrap music!",
	"Why did the yoga instructor get fired? She was caught in too many compromising positions.",
	"What do you call a man with a rubber toe? Roberto!",
	"I used to run a dating service for chickens, but I was struggling to make hens meet.",
	"What do you call a magician who lost their magic? Ian.",
	"Why did the scarecrow become a successful motivational speaker? He was outstanding in his field.",
	"What do you call a parade of rabbits hopping backwards? A receding hare-line!",
	"I went to buy some camo pants but I couldn't find any.",
	"What do you call a bee that can't make up its mind? A maybe!",
	"I used to be a personal trainer, but then I gave my too-weak notice.",
	"What do sprinters eat before a race? Nothing, they fast!",
	"Why don't oysters share their pearls? Because they're shellfish!",
	"What do you call a pony with a cough? A little horse!",
];

const loremSentences = [
	'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
	'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
	'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.',
	'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore.',
	'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia.',
	'Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit.',
	'Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet.',
	'Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis.',
	'Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse.',
	'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis.',
	'Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit.',
	'Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus.',
	'Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis.',
	'Nulla facilisi morbi tempus iaculis urna id volutpat lacus laoreet.',
	'Viverra accumsan in nisl nisi scelerisque eu ultrices vitae auctor.',
	'Pellentesque habitant morbi tristique senectus et netus et malesuada fames.',
	'Cras tincidunt lobortis feugiat vivamus at augue eget arcu dictum.',
	'Ultrices sagittis orci a scelerisque purus semper eget duis at.',
	'Amet consectetur adipiscing elit pellentesque habitant morbi tristique senectus.',
	'Faucibus interdum posuere lorem ipsum dolor sit amet consectetur adipiscing.',
];

/**
 * Shuffle array using Fisher-Yates algorithm (returns new array)
 */
function shuffle(arr) {
	const a = [...arr];
	for (let i = a.length - 1; i > 0; i--) {
		const j = Math.floor(Math.random() * (i + 1));
		[a[i], a[j]] = [a[j], a[i]];
	}
	return a;
}

/**
 * Parse a dummy text command like =dad(3,5) or =lorem()
 * Returns null if text is not a valid command.
 */
export function matchDummyCommand(text) {
	const match = text.trim().match(/^=(dadjokes|dad|lorem)\((\d*),?\s*(\d*)\)$/i);
	if (!match) return null;

	const type = match[1].toLowerCase();
	const paragraphs = Math.min(parseInt(match[2]) || 3, 20);
	const sentences = Math.min(parseInt(match[3]) || 3, 20);

	return { type, paragraphs, sentences };
}

/**
 * Generate dummy text paragraphs.
 * Returns an array of paragraph strings.
 */
export function generateDummyText(command) {
	const { type, paragraphs, sentences } = command;
	const source = (type === 'lorem') ? loremSentences : dadJokes;

	const result = [];
	const shuffled = shuffle(source);
	let idx = 0;

	for (let p = 0; p < paragraphs; p++) {
		const parts = [];
		for (let s = 0; s < sentences; s++) {
			parts.push(shuffled[idx % shuffled.length]);
			idx++;
		}
		result.push(parts.join(' '));
	}

	return result;
}

/**
 * TipTap Extension: DummyTextGenerator
 *
 * Uses a ProseMirror plugin to intercept Enter key with high priority,
 * before StarterKit's handlers. Checks if the current paragraph contains
 * a dummy text command (=dad(), =lorem(), etc.) and replaces it
 * with generated content.
 */
export const DummyTextExtension = Extension.create({
	name: 'dummyTextGenerator',

	addProseMirrorPlugins() {
		const editor = this.editor;

		return [
			new Plugin({
				key: new PluginKey('dummyTextGenerator'),
				props: {
					handleKeyDown(view, event) {
						if (event.key !== 'Enter') return false;

						const { state } = view;
						const { $from } = state.selection;

						// Only handle paragraphs
						const node = $from.parent;
						if (node.type.name !== 'paragraph') return false;

						const text = node.textContent;
						const command = matchDummyCommand(text);
						if (!command) return false;

						// Prevent the default Enter
						event.preventDefault();

						// Find the position range of the current paragraph
						const from = $from.before();
						const to = $from.after();

						// Generate content as HTML paragraphs
						const paragraphs = generateDummyText(command);
						const html = paragraphs.map(p => `<p>${p}</p>`).join('');

						// Replace the command paragraph with generated content
						editor.chain()
							.focus()
							.deleteRange({ from, to })
							.insertContentAt(from, html)
							.run();

						return true;
					},
				},
			}),
		];
	},
});
