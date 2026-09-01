/**
 * Advanced Multi-Mode Client-Side Password Generator.
 *
 * Implements SRS FR-5:
 * - Three Generation Modes: Random String, Memorable Passphrase, and Pronounceable Syllables.
 * - Real-time Bit-Entropy Calculation and Estimated Offline Crack Time.
 * - Automatic Breach Checking via k-Anonymity (auto-regenerates if breached when enabled).
 * - Ephemeral In-Memory History of the last 5 generated passwords (never persisted to disk).
 */

import { checkPasswordBreach } from './breach.js';
import { randomBytes } from './crypto.js';

/** Curated wordlist for memorable passphrases (EFF-style safe common words). */
const PASSPHRASE_WORDS = [
    'abandon', 'ability', 'absent', 'absorb', 'abstract', 'academy', 'account', 'accuse',
    'achieve', 'acoustic', 'acquire', 'across', 'action', 'actor', 'actress', 'actual',
    'adapt', 'address', 'adjust', 'admit', 'adult', 'advance', 'advice', 'aerobic',
    'affair', 'afford', 'afraid', 'agent', 'agree', 'ahead', 'airline', 'airport',
    'alarm', 'album', 'alcohol', 'alert', 'alien', 'allied', 'almond', 'almost',
    'alone', 'alpha', 'already', 'alter', 'always', 'amateur', 'amazing', 'among',
    'amount', 'amused', 'analyst', 'anchor', 'ancient', 'anger', 'angle', 'angry',
    'animal', 'ankle', 'announce', 'annual', 'another', 'answer', 'antenna', 'antique',
    'anxiety', 'anybody', 'apart', 'apology', 'appear', 'apple', 'approve', 'april',
    'arcade', 'arch', 'arctic', 'area', 'arena', 'argue', 'armor', 'army',
    'around', 'arrange', 'arrest', 'arrive', 'arrow', 'artist', 'artwork', 'aspect',
    'assault', 'asset', 'assist', 'assume', 'asthma', 'athlete', 'atom', 'attack',
    'attend', 'attitude', 'attract', 'auction', 'audit', 'august', 'aunt', 'author',
    'auto', 'autumn', 'average', 'avocado', 'avoid', 'awake', 'aware', 'away',
    'awesome', 'awful', 'awkward', 'axis', 'baby', 'bachelor', 'bacon', 'badge',
    'bagel', 'balance', 'balcony', 'ball', 'bamboo', 'banana', 'banner', 'barber',
    'barely', 'bargain', 'barrel', 'base', 'basic', 'basket', 'battle', 'beach',
    'beacon', 'beast', 'beauty', 'because', 'become', 'beef', 'before', 'begin',
    'behave', 'behind', 'believe', 'below', 'belt', 'bench', 'benefit', 'best',
    'betray', 'better', 'between', 'beyond', 'bicycle', 'binary', 'biology', 'bird',
    'birth', 'bitter', 'black', 'blade', 'blame', 'blanket', 'blast', 'bleak',
    'bless', 'blind', 'blood', 'blossom', 'blouse', 'blue', 'blur', 'board',
    'boat', 'body', 'boil', 'bomb', 'bone', 'bonus', 'book', 'boost',
    'border', 'bored', 'borrow', 'boss', 'bottom', 'bounce', 'boxing', 'bracket',
    'brain', 'brand', 'brass', 'brave', 'bread', 'breeze', 'brick', 'bridge',
    'brief', 'bright', 'bring', 'brisk', 'broccoli', 'broken', 'bronze', 'broom',
    'brother', 'brown', 'brush', 'bubble', 'budget', 'buffalo', 'build', 'bulb',
    'bullet', 'bundle', 'bunker', 'burden', 'burger', 'burst', 'bus', 'business',
    'busy', 'butter', 'buyer', 'buzz', 'cabbage', 'cabin', 'cable', 'cactus',
    'cage', 'cake', 'camera', 'camp', 'canal', 'canary', 'cancel', 'candle',
    'cannon', 'canoe', 'canvas', 'canyon', 'capable', 'capital', 'captain', 'carbon',
    'card', 'cargo', 'carpet', 'carrier', 'carrot', 'carry', 'cart', 'case',
    'cash', 'casino', 'castle', 'casual', 'catalog', 'catch', 'category', 'cattle',
    'caution', 'cave', 'ceiling', 'celery', 'cement', 'census', 'century', 'cereal',
    'certain', 'chair', 'chalk', 'champion', 'change', 'chaos', 'chapter', 'charge',
    'chase', 'chat', 'cheap', 'check', 'cheese', 'chef', 'cherry', 'chest',
    'chicken', 'chief', 'child', 'chimney', 'choice', 'choose', 'chronic', 'chuckle',
    'chunk', 'churn', 'cigar', 'cinnamon', 'circle', 'citizen', 'city', 'civil',
    'claim', 'clap', 'clarify', 'claw', 'clay', 'clean', 'clerk', 'clever',
    'click', 'client', 'cliff', 'climb', 'clinic', 'clip', 'clock', 'clog',
    'close', 'cloth', 'cloud', 'clown', 'club', 'clump', 'cluster', 'clutch',
    'coach', 'coast', 'coconut', 'code', 'coffee', 'coil', 'coin', 'collect',
    'color', 'column', 'combine', 'comfort', 'comic', 'common', 'company', 'concert',
    'conduct', 'confirm', 'congress', 'connect', 'consider', 'control', 'convince', 'cook',
    'cool', 'copper', 'coral', 'core', 'corn', 'corner', 'correct', 'cost',
    'cotton', 'couch', 'country', 'couple', 'course', 'cousin', 'cover', 'coyote',
    'crack', 'cradle', 'craft', 'cram', 'crane', 'crash', 'crater', 'crawl',
    'crazy', 'cream', 'credit', 'creek', 'crew', 'cricket', 'crime', 'crisp',
    'critic', 'crop', 'cross', 'crouch', 'crowd', 'crucial', 'cruel', 'cruise',
    'crumble', 'crunch', 'crush', 'crystal', 'cube', 'culture', 'cup', 'cupboard',
    'curious', 'current', 'curtain', 'curve', 'cushion', 'custom', 'cute', 'cycle',
    'cylinder', 'daddy', 'damage', 'dance', 'danger', 'daring', 'dark', 'darling',
    'database', 'daughter', 'dawn', 'day', 'deal', 'debate', 'debris', 'decade',
    'december', 'decide', 'decline', 'decorate', 'decrease', 'deer', 'defense', 'define',
    'defy', 'degree', 'delay', 'deliver', 'demand', 'demise', 'denial', 'dentist',
    'depot', 'depth', 'deputy', 'derive', 'describe', 'desert', 'design', 'desk',
    'despair', 'destroy', 'detail', 'detect', 'develop', 'device', 'devote', 'diagram',
    'dial', 'diamond', 'diary', 'dice', 'diesel', 'diet', 'differ', 'digital',
    'dignity', 'dilemma', 'dinner', 'dinosaur', 'direct', 'dirt', 'disagree', 'discover',
    'disease', 'dish', 'dismiss', 'display', 'distance', 'divert', 'divide', 'divorce',
    'dizzy', 'doctor', 'document', 'dog', 'doll', 'dolphin', 'domain', 'donate',
    'donkey', 'donor', 'door', 'dose', 'double', 'dove', 'draft', 'dragon',
    'drama', 'drastic', 'draw', 'dream', 'dress', 'drift', 'drill', 'drink',
    'drip', 'drive', 'drop', 'drum', 'dry', 'duck', 'dumb', 'dune',
    'during', 'dust', 'dutch', 'duty', 'dwarf', 'dynamic', 'eager', 'eagle',
    'early', 'earn', 'earth', 'easily', 'east', 'easy', 'echo', 'ecology',
    'economy', 'edge', 'edit', 'educate', 'effort', 'egg', 'eight', 'either',
    'elbow', 'elder', 'electric', 'elegant', 'element', 'elephant', 'elevator', 'elite',
    'else', 'embark', 'embody', 'embrace', 'emerge', 'emotion', 'employ', 'empower',
    'empty', 'enable', 'enact', 'end', 'endless', 'endorse', 'enemy', 'energy',
    'enforce', 'engage', 'engine', 'enhance', 'enjoy', 'enlist', 'enough', 'enrich',
    'enroll', 'ensure', 'enter', 'entire', 'entry', 'envelope', 'episode', 'equal',
    'equip', 'era', 'erase', 'erode', 'erosion', 'error', 'erupt', 'escape',
    'essay', 'essence', 'estate', 'eternal', 'ethics', 'evidence', 'evil', 'evoke',
    'evolve', 'exact', 'example', 'excess', 'exchange', 'excite', 'exclude', 'excuse',
    'execute', 'exercise', 'exhaust', 'exhibit', 'exile', 'exist', 'exit', 'exotic',
    'expand', 'expect', 'expire', 'explain', 'expose', 'express', 'extend', 'extra',
    'eye', 'eyebrow', 'fabric', 'face', 'faculty', 'fade', 'faint', 'faith',
    'fall', 'false', 'fame', 'family', 'famous', 'fan', 'fancy', 'fantasy',
    'farm', 'fashion', 'fat', 'fatal', 'father', 'fatigue', 'fault', 'favorite',
    'feature', 'february', 'federal', 'fee', 'feed', 'feel', 'female', 'fence',
    'festival', 'fetch', 'fever', 'few', 'fiber', 'fiction', 'field', 'figure',
    'file', 'film', 'filter', 'final', 'find', 'fine', 'finger', 'finish',
    'fire', 'firm', 'first', 'fiscal', 'fish', 'fit', 'fitness', 'fix',
    'flag', 'flame', 'flash', 'flat', 'flavor', 'flee', 'flight', 'flip',
    'float', 'flock', 'floor', 'flower', 'fluid', 'flush', 'fly', 'foam',
    'focus', 'fog', 'foil', 'fold', 'follow', 'food', 'foot', 'force',
    'forest', 'forget', 'fork', 'fortune', 'forum', 'forward', 'fossil', 'foster',
    'found', 'fox', 'fragile', 'frame', 'frequent', 'fresh', 'friend', 'fringe',
    'frog', 'front', 'frost', 'frown', 'frozen', 'fruit', 'fuel', 'fun',
    'funny', 'furnace', 'fury', 'future', 'gadget', 'gain', 'galaxy', 'gallery',
    'game', 'gap', 'garage', 'garbage', 'garden', 'garlic', 'garment', 'gas',
    'gasp', 'gate', 'gather', 'gauge', 'gaze', 'general', 'genius', 'genre',
    'gentle', 'genuine', 'gesture', 'ghost', 'giant', 'gift', 'giggle', 'ginger',
    'giraffe', 'girl', 'give', 'glad', 'glance', 'glare', 'glass', 'glide',
    'glimpse', 'globe', 'gloom', 'glory', 'glove', 'glow', 'glue', 'goat',
    'goddess', 'gold', 'good', 'goose', 'gorilla', 'gospel', 'gossip', 'govern',
    'gown', 'grab', 'grace', 'grain', 'grant', 'grape', 'grass', 'gravity',
    'great', 'green', 'grid', 'grief', 'grit', 'grocery', 'group', 'grow',
    'grunt', 'guard', 'guess', 'guide', 'guilt', 'guitar', 'gun', 'gym',
    'habit', 'hair', 'half', 'hammer', 'hamster', 'hand', 'handle', 'harbor',
    'hard', 'harsh', 'harvest', 'hat', 'have', 'hawk', 'hazard', 'head',
    'health', 'heart', 'heavy', 'hedgehog', 'height', 'hello', 'helmet', 'help',
    'hen', 'hero', 'hidden', 'high', 'hill', 'hint', 'hip', 'hire',
    'history', 'hobby', 'hockey', 'hold', 'hole', 'holiday', 'hollow', 'home',
    'honey', 'hood', 'hope', 'horn', 'horror', 'horse', 'hospital', 'host',
    'hotel', 'hour', 'hover', 'hub', 'huge', 'human', 'humble', 'humor',
    'hundred', 'hungry', 'hunt', 'hurdle', 'hurry', 'hurt', 'husband', 'hybrid',
    'ice', 'icon', 'idea', 'identify', 'idle', 'ignore', 'illness', 'illegal',
    'image', 'imitate', 'immense', 'immune', 'impact', 'impose', 'improve', 'impulse',
    'inch', 'include', 'income', 'increase', 'index', 'indicate', 'indoor', 'industry',
    'infant', 'inflict', 'inform', 'inhale', 'inherit', 'initial', 'inject', 'injury',
    'inmate', 'inner', 'innocent', 'input', 'inquiry', 'insane', 'insect', 'inside',
    'inspire', 'install', 'intact', 'interest', 'into', 'invest', 'invite', 'involve',
    'iron', 'island', 'isolate', 'issue', 'item', 'ivory', 'jacket', 'jaguar',
    'jar', 'jazz', 'jealous', 'jeans', 'jelly', 'jewel', 'job', 'join',
    'joke', 'journey', 'joy', 'judge', 'juice', 'jump', 'jungle', 'junior',
    'junk', 'just', 'kangaroo', 'keen', 'keep', 'ketchup', 'key', 'kick',
    'kid', 'kidney', 'kind', 'kingdom', 'kiss', 'kit', 'kitchen', 'kite',
    'kitten', 'kiwi', 'knee', 'knife', 'knock', 'know', 'lab', 'label',
    'labor', 'ladder', 'lady', 'lake', 'lamp', 'language', 'laptop', 'large',
    'later', 'latin', 'laugh', 'laundry', 'lava', 'law', 'lawn', 'lawsuit',
    'layer', 'lazy', 'leader', 'leaf', 'learn', 'leave', 'lecture', 'left',
    'leg', 'legal', 'legend', 'leisure', 'lemon', 'lend', 'length', 'lens',
    'leopard', 'lesson', 'letter', 'level', 'liar', 'liberty', 'library', 'license',
    'life', 'lift', 'light', 'like', 'limb', 'limit', 'link', 'lion',
    'liquid', 'list', 'little', 'live', 'lizard', 'load', 'loan', 'lobster',
    'local', 'lock', 'logic', 'lonely', 'long', 'loop', 'lottery', 'loud',
    'lounge', 'love', 'loyal', 'lucky', 'luggage', 'lumber', 'lunar', 'lunch',
    'luxury', 'lyrics', 'machine', 'mad', 'magic', 'magnet', 'maid', 'mail',
    'main', 'major', 'make', 'mammal', 'man', 'manage', 'mandate', 'mango',
    'mansion', 'manual', 'maple', 'marble', 'march', 'margin', 'marine', 'market',
    'marriage', 'mask', 'mass', 'master', 'match', 'material', 'math', 'matrix',
    'matter', 'maximum', 'maze', 'meadow', 'mean', 'measure', 'meat', 'mechanic',
    'medal', 'media', 'melody', 'melt', 'member', 'memory', 'mention', 'menu',
    'mercy', 'merge', 'merit', 'merry', 'mesh', 'message', 'metal', 'method',
    'middle', 'midnight', 'milk', 'million', 'mimic', 'mind', 'minimum', 'minor',
    'minute', 'miracle', 'mirror', 'misery', 'miss', 'mistake', 'mix', 'mixed',
    'mixture', 'mobile', 'model', 'modify', 'module', 'moist', 'moment', 'monitor',
    'monkey', 'monster', 'month', 'moon', 'moral', 'more', 'morning', 'mosquito',
    'mother', 'motion', 'motor', 'mountain', 'mouse', 'move', 'movie', 'much',
    'muffin', 'mule', 'multiply', 'muscle', 'museum', 'mushroom', 'music', 'must',
    'mutual', 'myself', 'mystery', 'myth', 'naive', 'name', 'napkin', 'narrow',
    'nasty', 'nation', 'nature', 'near', 'neck', 'need', 'negative', 'neglect',
    'neither', 'nephew', 'nerve', 'nest', 'net', 'network', 'neutral', 'never',
    'news', 'next', 'nice', 'night', 'noble', 'noise', 'nominee', 'noodle',
    'normal', 'north', 'nose', 'notable', 'note', 'nothing', 'notice', 'novel',
    'now', 'nuclear', 'number', 'nurse', 'nut', 'oak', 'obey', 'object',
    'oblige', 'obscure', 'observe', 'obtain', 'obvious', 'occur', 'ocean', 'october',
    'odor', 'off', 'offer', 'office', 'often', 'oil', 'okay', 'old',
    'olive', 'olympic', 'omit', 'once', 'one', 'onion', 'online', 'only',
    'open', 'opera', 'opinion', 'oppose', 'option', 'orange', 'orbit', 'orchard',
    'order', 'ordinary', 'organ', 'orient', 'original', 'orphan', 'ostrich', 'other',
    'outdoor', 'outer', 'output', 'outside', 'oval', 'oven', 'over', 'own',
    'owner', 'oxygen', 'oyster', 'ozone', 'pact', 'paddle', 'page', 'pair',
    'palace', 'palm', 'panda', 'panel', 'panic', 'panther', 'paper', 'parade',
    'parent', 'park', 'parrot', 'party', 'pass', 'patch', 'path', 'patient',
    'patrol', 'pattern', 'pause', 'pave', 'payment', 'peace', 'peanut', 'pear',
    'peasant', 'pelican', 'pen', 'penalty', 'pencil', 'people', 'pepper', 'perfect',
    'permit', 'person', 'pet', 'phone', 'photo', 'phrase', 'physical', 'piano',
    'picnic', 'picture', 'piece', 'pig', 'pigeon', 'pill', 'pilot', 'pink',
    'pioneer', 'pipe', 'pistol', 'pitch', 'pizza', 'place', 'planet', 'plastic',
    'plate', 'play', 'please', 'pledge', 'pluck', 'plug', 'plunge', 'poem',
    'poet', 'point', 'polar', 'pole', 'police', 'pond', 'pony', 'pool',
    'popular', 'portion', 'position', 'possible', 'post', 'potato', 'pottery', 'poverty',
    'powder', 'power', 'practice', 'praise', 'predict', 'prefer', 'prepare', 'present',
    'pretty', 'prevent', 'price', 'pride', 'primary', 'print', 'priority', 'prison',
    'private', 'prize', 'problem', 'process', 'produce', 'profit', 'program', 'project',
    'promote', 'proof', 'property', 'prosper', 'protect', 'proud', 'provide', 'public',
    'pudding', 'pull', 'pulp', 'pulse', 'pumpkin', 'punch', 'pupil', 'puppy',
    'purchase', 'purity', 'purpose', 'purse', 'push', 'put', 'puzzle', 'pyramid',
    'quality', 'quantum', 'quarter', 'question', 'quick', 'quit', 'quiz', 'quote',
    'rabbit', 'raccoon', 'race', 'rack', 'radar', 'radio', 'rail', 'rain',
    'raise', 'rally', 'ramp', 'ranch', 'random', 'range', 'rapid', 'rare',
    'rate', 'rather', 'raven', 'raw', 'razor', 'ready', 'real', 'reason',
    'rebel', 'rebuild', 'recall', 'receive', 'recipe', 'record', 'recycle', 'reduce',
    'reflect', 'reform', 'refuse', 'region', 'regret', 'regular', 'reject', 'relax',
    'release', 'relief', 'rely', 'remain', 'remember', 'remind', 'remove', 'render',
    'renew', 'rent', 'reopen', 'repair', 'repeat', 'replace', 'report', 'require',
    'rescue', 'resemble', 'resist', 'resource', 'response', 'result', 'retire', 'retreat',
    'return', 'reunion', 'reveal', 'review', 'reward', 'rhythm', 'rib', 'ribbon',
    'rice', 'rich', 'ride', 'ridge', 'rifle', 'right', 'rigid', 'ring',
    'riot', 'ripple', 'risk', 'ritual', 'rival', 'river', 'road', 'roast',
    'robot', 'robust', 'rocket', 'romance', 'roof', 'rookie', 'room', 'rose',
    'rotate', 'rough', 'round', 'route', 'royal', 'rubber', 'rude', 'rug',
    'rule', 'run', 'runway', 'rural', 'sad', 'saddle', 'sadness', 'safe',
    'sail', 'salad', 'salmon', 'salon', 'salt', 'salute', 'same', 'sample',
    'sand', 'satisfy', 'sauce', 'sausage', 'save', 'say', 'scale', 'scan',
    'scare', 'scatter', 'scene', 'scheme', 'school', 'science', 'scissors', 'scooter',
    'scope', 'score', 'scout', 'scrap', 'scream', 'screen', 'script', 'scrub',
    'sea', 'search', 'season', 'seat', 'second', 'secret', 'section', 'security',
    'seed', 'seek', 'segment', 'select', 'sell', 'seminar', 'senior', 'sense',
    'sentence', 'series', 'service', 'session', 'settle', 'setup', 'seven', 'shadow',
    'shaft', 'shallow', 'share', 'shed', 'shell', 'sheriff', 'shield', 'shift',
    'shine', 'ship', 'shiver', 'shock', 'shoe', 'shoot', 'shop', 'short',
    'shoulder', 'shove', 'shrimp', 'shrug', 'shuffle', 'shy', 'sibling', 'sick',
    'side', 'siege', 'sight', 'sign', 'silent', 'silk', 'silly', 'silver',
    'similar', 'simple', 'since', 'sing', 'siren', 'sister', 'situate', 'six',
    'size', 'skate', 'sketch', 'ski', 'skill', 'skin', 'skirt', 'skull',
    'slab', 'slam', 'sleep', 'slender', 'slice', 'slide', 'slight', 'slim',
    'slogan', 'slot', 'slow', 'slush', 'small', 'smart', 'smile', 'smoke',
    'smooth', 'snack', 'snake', 'snap', 'sniff', 'snow', 'soap', 'soccer',
    'social', 'sock', 'soda', 'soft', 'solar', 'soldier', 'solid', 'solution',
    'solve', 'someone', 'song', 'soon', 'sorry', 'sort', 'soul', 'sound',
    'soup', 'source', 'south', 'space', 'spare', 'spatial', 'spawn', 'speak',
    'special', 'speed', 'spell', 'spend', 'sphere', 'spice', 'spider', 'spike',
    'spin', 'spirit', 'split', 'spoil', 'sponsor', 'spoon', 'sport', 'spot',
    'spray', 'spread', 'spring', 'spy', 'square', 'squeeze', 'squirrel', 'stable',
    'stadium', 'staff', 'stage', 'stairs', 'stamp', 'stand', 'start', 'state',
    'stay', 'steak', 'steel', 'stem', 'step', 'stereo', 'stick', 'still',
    'sting', 'stock', 'stomach', 'stone', 'stool', 'story', 'stove', 'strategy',
    'street', 'strike', 'strong', 'struggle', 'student', 'stuff', 'stumble', 'style',
    'subject', 'submit', 'subway', 'success', 'such', 'sudden', 'suffer', 'sugar',
    'suggest', 'suit', 'summer', 'sun', 'sunny', 'sunset', 'super', 'supply',
    'supreme', 'sure', 'surface', 'surge', 'surprise', 'surround', 'survey', 'suspect',
    'sustain', 'swallow', 'swamp', 'swap', 'swarm', 'swear', 'sweet', 'swift',
    'swim', 'swing', 'switch', 'sword', 'symbol', 'symptom', 'syrup', 'system',
    'table', 'tackle', 'tag', 'tail', 'talent', 'talk', 'tank', 'tape',
    'target', 'task', 'taste', 'tattoo', 'taxi', 'teach', 'team', 'tell',
    'ten', 'tenant', 'tennis', 'tent', 'term', 'test', 'text', 'thank',
    'theme', 'theory', 'therapy', 'thing', 'think', 'third', 'thirty', 'though',
    'thought', 'thousand', 'threat', 'three', 'thrift', 'thrive', 'throat', 'thumb',
    'thunder', 'ticket', 'tide', 'tiger', 'tilt', 'timber', 'time', 'tiny',
    'tip', 'tired', 'tissue', 'title', 'toast', 'tobacco', 'today', 'toddler',
    'toe', 'together', 'toilet', 'token', 'tomato', 'tomorrow', 'tone', 'tongue',
    'tonight', 'tool', 'tooth', 'top', 'topic', 'topple', 'torch', 'tornado',
    'tortoise', 'total', 'tourist', 'toward', 'tower', 'town', 'toy', 'track',
    'trade', 'traffic', 'tragic', 'train', 'transfer', 'trap', 'trash', 'travel',
    'tray', 'treat', 'tree', 'trend', 'trial', 'tribe', 'trick', 'trigger',
    'trim', 'trip', 'trophy', 'trouble', 'truck', 'true', 'truly', 'trumpet',
    'trust', 'truth', 'try', 'tube', 'tuition', 'tumble', 'tuna', 'tunnel',
    'turkey', 'turn', 'turtle', 'twelve', 'twenty', 'twice', 'twin', 'twist',
    'two', 'type', 'typical', 'ugly', 'umbrella', 'unable', 'unaware', 'uncle',
    'uncover', 'under', 'undo', 'unfair', 'unfold', 'unhappy', 'uniform', 'unique',
    'unit', 'universe', 'unknown', 'unlock', 'until', 'unusual', 'unveil', 'update',
    'upgrade', 'uphold', 'upon', 'upper', 'upset', 'urban', 'urge', 'usage',
    'use', 'used', 'useful', 'useless', 'usual', 'utility', 'vacant', 'vacuum',
    'vague', 'valid', 'valley', 'valve', 'van', 'vanish', 'vapor', 'various',
    'vast', 'vault', 'vehicle', 'velvet', 'vendor', 'venture', 'venue', 'verb',
    'verify', 'version', 'very', 'vessel', 'veteran', 'viable', 'vibrant', 'vicious',
    'victory', 'video', 'view', 'village', 'vintage', 'violin', 'virtual', 'virus',
    'visa', 'visit', 'visual', 'vital', 'vivid', 'vocal', 'voice', 'void',
    'volcano', 'volume', 'vote', 'voyage', 'wage', 'wagon', 'wait', 'walk',
    'wall', 'walnut', 'want', 'warfare', 'warm', 'warrior', 'wash', 'wasp',
    'waste', 'water', 'wave', 'way', 'wealth', 'weapon', 'wear', 'weasel',
    'weather', 'web', 'wedding', 'weekend', 'weird', 'welcome', 'west', 'wet',
    'whale', 'what', 'wheat', 'wheel', 'when', 'where', 'whip', 'whisper',
    'wide', 'width', 'wife', 'wild', 'will', 'win', 'window', 'wine',
    'wing', 'wink', 'winner', 'winter', 'wire', 'wisdom', 'wise', 'wish',
    'witness', 'wolf', 'woman', 'wonder', 'wood', 'wool', 'word', 'work',
    'world', 'worry', 'worth', 'wrap', 'wreck', 'wrestle', 'wrist', 'write',
    'wrong', 'yard', 'year', 'yellow', 'you', 'young', 'youth', 'zebra',
    'zero', 'zone', 'zoo',
];

/** Pronounceable syllable components (consonants and vowels). */
const CONSONANTS = ['b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'r', 's', 't', 'v', 'w', 'z', 'ch', 'sh', 'th', 'ph', 'st'];
const VOWELS = ['a', 'e', 'i', 'o', 'u', 'ai', 'ea', 'ee', 'oa', 'oo', 'ou'];

/** In-memory session history of last 5 generated passwords (ephemeral). */
const sessionHistory = [];

/**
 * Generates a random integer in the range [0, max).
 * Uses cryptographically secure random values.
 *
 * @param {number} max - Upper bound (exclusive).
 * @returns {number} Random integer.
 */
function secureRandomInt(max) {
    if (max <= 0) return 0;
    const array = new Uint32Array(1);
    window.crypto.getRandomValues(array);
    return array[0] % max;
}

/**
 * Generate a random character string password.
 *
 * @param {Object} options - Configuration options.
 * @param {number} options.length - Password length (default 20).
 * @param {boolean} options.uppercase - Include uppercase (A-Z).
 * @param {boolean} options.lowercase - Include lowercase (a-z).
 * @param {boolean} options.numbers - Include digits (0-9).
 * @param {boolean} options.symbols - Include special symbols.
 * @param {boolean} options.excludeAmbiguous - Exclude ambiguous characters (0, O, 1, l, I).
 * @returns {string} The generated password.
 */
export function generateRandomString(options = {}) {
    const length = options.length || 20;
    let uppercaseChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    let lowercaseChars = 'abcdefghijklmnopqrstuvwxyz';
    let numberChars = '0123456789';
    let symbolChars = '!@#$%^&*()_+-=[]{}|;:,.<>?';

    if (options.excludeAmbiguous) {
        uppercaseChars = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        lowercaseChars = 'abcdefghijkmnopqrstuvwxyz';
        numberChars = '23456789';
        symbolChars = '!#$%&*+-=?@^_';
    }

    let charPool = '';
    const guaranteed = [];

    if (options.uppercase !== false) {
        charPool += uppercaseChars;
        guaranteed.push(uppercaseChars[secureRandomInt(uppercaseChars.length)]);
    }
    if (options.lowercase !== false) {
        charPool += lowercaseChars;
        guaranteed.push(lowercaseChars[secureRandomInt(lowercaseChars.length)]);
    }
    if (options.numbers !== false) {
        charPool += numberChars;
        guaranteed.push(numberChars[secureRandomInt(numberChars.length)]);
    }
    if (options.symbols !== false) {
        charPool += symbolChars;
        guaranteed.push(symbolChars[secureRandomInt(symbolChars.length)]);
    }

    if (charPool.length === 0) {
        charPool = lowercaseChars + numberChars;
    }

    const passwordArray = [...guaranteed];
    while (passwordArray.length < length) {
        passwordArray.push(charPool[secureRandomInt(charPool.length)]);
    }

    // Shuffle the array using Fisher-Yates with secure randomness
    for (let i = passwordArray.length - 1; i > 0; i--) {
        const j = secureRandomInt(i + 1);
        [passwordArray[i], passwordArray[j]] = [passwordArray[j], passwordArray[i]];
    }

    return passwordArray.join('');
}

/**
 * Generate a memorable passphrase composed of random dictionary words.
 *
 * @param {Object} options - Configuration options.
 * @param {number} options.wordCount - Number of words (default 4).
 * @param {string} options.separator - Separator string (default '-').
 * @param {boolean} options.capitalize - Capitalize the first letter of each word.
 * @param {boolean} options.includeNumber - Append/insert a random 2-digit number.
 * @returns {string} The generated passphrase.
 */
export function generatePassphrase(options = {}) {
    const wordCount = options.wordCount || 4;
    const separator = options.separator ?? '-';
    const capitalize = options.capitalize ?? true;
    const includeNumber = options.includeNumber ?? true;

    const selectedWords = [];
    for (let i = 0; i < wordCount; i++) {
        let word = PASSPHRASE_WORDS[secureRandomInt(PASSPHRASE_WORDS.length)];
        if (capitalize) {
            word = word.charAt(0).toUpperCase() + word.slice(1);
        }
        selectedWords.push(word);
    }

    if (includeNumber) {
        const num = secureRandomInt(90) + 10; // 10-99
        selectedWords.push(num.toString());
    }

    return selectedWords.join(separator);
}

/**
 * Generate a pronounceable password using alternating consonant-vowel syllables.
 *
 * @param {Object} options - Configuration options.
 * @param {number} options.syllableCount - Number of syllables (default 4).
 * @param {boolean} options.capitalize - Capitalize initial letters.
 * @param {boolean} options.includeNumber - Include a trailing number.
 * @param {boolean} options.includeSymbol - Include a trailing special symbol.
 * @returns {string} The generated pronounceable password.
 */
export function generatePronounceable(options = {}) {
    const syllableCount = options.syllableCount || 4;
    const capitalize = options.capitalize ?? true;
    const includeNumber = options.includeNumber ?? true;
    const includeSymbol = options.includeSymbol ?? true;

    let result = '';
    for (let i = 0; i < syllableCount; i++) {
        const c = CONSONANTS[secureRandomInt(CONSONANTS.length)];
        const v = VOWELS[secureRandomInt(VOWELS.length)];
        let syllable = c + v;
        if (capitalize && i === 0) {
            syllable = syllable.charAt(0).toUpperCase() + syllable.slice(1);
        }
        result += syllable;
    }

    if (includeNumber) {
        result += (secureRandomInt(90) + 10).toString();
    }
    if (includeSymbol) {
        const symbols = '!@#$%^&*';
        result += symbols[secureRandomInt(symbols.length)];
    }

    return result;
}

/**
 * Calculate password bit-entropy and estimate offline cracking time.
 *
 * Entropy formula: E = L * log2(R)
 * Crack time estimated assuming 10^10 hashes per second (high-end GPU cluster).
 *
 * @param {string} password - The candidate password.
 * @returns {{entropy: number, strength: string, crackTime: string, color: string}} Metrics.
 */
export function calculateEntropy(password) {
    if (!password || password.length === 0) {
        return { entropy: 0, strength: window.zkpmT('None'), crackTime: window.zkpmT('Instant'), color: 'gray' };
    }

    let poolSize = 0;
    if (/[a-z]/.test(password)) poolSize += 26;
    if (/[A-Z]/.test(password)) poolSize += 26;
    if (/[0-9]/.test(password)) poolSize += 10;
    if (/[^a-zA-Z0-9]/.test(password)) poolSize += 33;

    if (poolSize === 0) poolSize = 1;

    const entropy = Math.round(password.length * (Math.log(poolSize) / Math.LN2));

    // Guess space size = 2^entropy
    // Guesses per second = 10,000,000,000 (10 GH/s)
    const seconds = Math.pow(2, entropy) / 1e10;

    // Localized crack-time estimation units
    let crackTime = window.zkpmT('Instant');
    if (seconds < 1) {
        crackTime = window.zkpmT('Instant');
    } else if (seconds < 60) {
        crackTime = window.zkpmT('seconds unit', { count: Math.round(seconds) });
    } else if (seconds < 3600) {
        crackTime = window.zkpmT('minutes unit', { count: Math.round(seconds / 60) });
    } else if (seconds < 86400) {
        crackTime = window.zkpmT('hours unit', { count: Math.round(seconds / 3600) });
    } else if (seconds < 31536000) {
        crackTime = window.zkpmT('days unit', { count: Math.round(seconds / 86400) });
    } else if (seconds < 3153600000) {
        crackTime = window.zkpmT('years unit', { count: Math.round(seconds / 31536000) });
    } else if (seconds < 3153600000000) {
        crackTime = window.zkpmT('millennia unit', { count: Math.round(seconds / 31536000000) });
    } else {
        crackTime = window.zkpmT('Centuries+');
    }

    let strength = window.zkpmT('Very Weak');
    let color = 'red';

    if (entropy >= 100) {
        strength = window.zkpmT('Very Strong');
        color = 'green';
    } else if (entropy >= 80) {
        strength = window.zkpmT('Strong');
        color = 'emerald';
    } else if (entropy >= 60) {
        strength = window.zkpmT('Reasonable');
        color = 'yellow';
    } else if (entropy >= 40) {
        strength = window.zkpmT('Weak');
        color = 'orange';
    }

    return { entropy, strength, crackTime, color };
}

/**
 * Record a generated password into the ephemeral in-memory session history (max 5).
 *
 * @param {string} password - The generated password.
 * @param {string} mode - The generation mode used.
 * @returns {Array<Object>} Updated history array.
 */
export function recordSessionHistory(password, mode = 'Random') {
    if (!password) return sessionHistory;

    sessionHistory.unshift({
        password,
        mode,
        timestamp: new Date().toLocaleTimeString(),
        entropy: calculateEntropy(password).entropy,
    });

    if (sessionHistory.length > 5) {
        sessionHistory.pop();
    }

    return sessionHistory;
}

/**
 * Returns the current ephemeral session history.
 *
 * @returns {Array<Object>} History array.
 */
export function getSessionHistory() {
    return sessionHistory;
}

/**
 * Full (purge-safe) badge class strings per entropy strength color.
 *
 * @type {Object<string, string>}
 */
const COLOR_BADGE_CLASSES = {
    gray: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
    red: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-800/60',
    orange: 'bg-orange-100 text-orange-700 border-orange-200 dark:bg-orange-950/60 dark:text-orange-300 dark:border-orange-800/60',
    yellow: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800/60',
    emerald: 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800/60',
    green: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-950/60 dark:text-green-300 dark:border-green-800/60',
};

/**
 * Wire the Password Generator UI on the standalone generator page or dashboard.
 *
 * @returns {void}
 */
export function initGenerator() {
    const container = document.getElementById('generator-app');
    if (container === null) return;

    const outputEl = document.getElementById('gen-output');
    const copyBtn = document.getElementById('gen-copy-btn');
    const refreshBtn = document.getElementById('gen-refresh-btn');
    const modeSelect = document.getElementById('gen-mode');
    const entropyBadge = document.getElementById('gen-entropy-badge');
    const crackTimeEl = document.getElementById('gen-crack-time');
    const breachWarningEl = document.getElementById('gen-breach-warning');
    const historyListEl = document.getElementById('gen-history-list');

    // Controls
    const lengthSlider = document.getElementById('gen-length');
    const lengthVal = document.getElementById('gen-length-val');
    const optUpper = document.getElementById('gen-opt-upper');
    const optLower = document.getElementById('gen-opt-lower');
    const optNumbers = document.getElementById('gen-opt-numbers');
    const optSymbols = document.getElementById('gen-opt-symbols');
    const optAmbiguous = document.getElementById('gen-opt-ambiguous');
    const optAutoBreach = document.getElementById('gen-opt-autobreach');

    const randomOptions = document.getElementById('gen-options-random');
    const passphraseOptions = document.getElementById('gen-options-passphrase');
    const pronounceableOptions = document.getElementById('gen-options-pronounceable');

    /**
     * Render the in-memory password history list.
     */
    function renderHistory() {
        if (!historyListEl) return;
        historyListEl.innerHTML = '';

        const history = getSessionHistory();
        if (history.length === 0) {
            historyListEl.innerHTML = '<p class="text-xs text-slate-400 italic">' + window.zkpmT('No generation history yet.') + '</p>';
            return;
        }

        history.forEach((item) => {
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-2 p-2 rounded-lg border border-slate-200 bg-white/70 text-xs dark:border-slate-700 dark:bg-slate-900/80';
            row.innerHTML = `
                <div class="truncate flex-1 font-mono text-slate-700 dark:text-slate-300"></div>
                <div class="flex items-center gap-2">
                    <span class="text-slate-400 text-[10px]">${item.mode} (${item.entropy}b)</span>
                    <button type="button" class="copy-history-btn px-2.5 py-1 rounded-lg border border-slate-200 bg-white font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">${window.zkpmT('Copy')}</button>
                </div>
            `;
            row.querySelector('.truncate').textContent = item.password;
            row.querySelector('.copy-history-btn').addEventListener('click', async () => {
                await navigator.clipboard.writeText(item.password);
                const btn = row.querySelector('.copy-history-btn');
                btn.textContent = window.zkpmT('Copied');
                setTimeout(() => { btn.textContent = window.zkpmT('Copy'); }, 1200);
            });
            historyListEl.appendChild(row);
        });
    }

    /**
     * Generate password according to current settings.
     */
    async function doGenerate() {
        const mode = modeSelect ? modeSelect.value : 'random';
        let password = '';

        if (mode === 'passphrase') {
            const wordCount = parseInt(document.getElementById('gen-words')?.value || '4', 10);
            const sep = document.getElementById('gen-separator')?.value ?? '-';
            const cap = document.getElementById('gen-passphrase-cap')?.checked ?? true;
            const num = document.getElementById('gen-passphrase-num')?.checked ?? true;
            password = generatePassphrase({ wordCount, separator: sep, capitalize: cap, includeNumber: num });
        } else if (mode === 'pronounceable') {
            const sylCount = parseInt(document.getElementById('gen-syl')?.value || '4', 10);
            const cap = document.getElementById('gen-pron-cap')?.checked ?? true;
            const num = document.getElementById('gen-pron-num')?.checked ?? true;
            const sym = document.getElementById('gen-pron-sym')?.checked ?? true;
            password = generatePronounceable({ syllableCount: sylCount, capitalize: cap, includeNumber: num, includeSymbol: sym });
        } else {
            const length = lengthSlider ? parseInt(lengthSlider.value, 10) : 20;
            const uppercase = optUpper ? optUpper.checked : true;
            const lowercase = optLower ? optLower.checked : true;
            const numbers = optNumbers ? optNumbers.checked : true;
            const symbols = optSymbols ? optSymbols.checked : true;
            const excludeAmbiguous = optAmbiguous ? optAmbiguous.checked : false;

            password = generateRandomString({ length, uppercase, lowercase, numbers, symbols, excludeAmbiguous });
        }

        if (outputEl) outputEl.value = password;

        // Calculate Entropy
        const metrics = calculateEntropy(password);
        if (entropyBadge) {
            entropyBadge.textContent = `${window.zkpmT(metrics.strength)} (${metrics.entropy} bits)`;
            entropyBadge.className = `inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${COLOR_BADGE_CLASSES[metrics.color] ?? 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'}`;
        }
        if (crackTimeEl) {
            crackTimeEl.textContent = metrics.crackTime;
        }

        // Live breach check
        if (breachWarningEl) {
            breachWarningEl.classList.add('hidden');
            breachWarningEl.textContent = window.zkpmT('Checking breach status…');

            const check = await checkPasswordBreach(password);
            if (check.breached) {
                if (optAutoBreach && optAutoBreach.checked) {
                    // Auto-regenerate if breached
                    return doGenerate();
                }
                breachWarningEl.textContent = `⚠️ ${window.zkpmT('Generator breach warning', { count: check.count.toLocaleString() })}`;
                breachWarningEl.className = 'mt-2 p-2.5 rounded-lg border border-red-800/60 bg-red-950/80 text-xs text-red-300';
                breachWarningEl.classList.remove('hidden');
            } else if (!check.error) {
                breachWarningEl.textContent = `✓ ${window.zkpmT('Clean generator status')}`;
                breachWarningEl.className = 'mt-2 p-2.5 rounded-lg border border-emerald-800/60 bg-emerald-950/80 text-xs text-emerald-300';
                breachWarningEl.classList.remove('hidden');
            }
        }

        // Record in session history
        recordSessionHistory(password, mode.toUpperCase());
        renderHistory();

        reportGeneration();
    }

    /**
     * Fire-and-forget an audit record so the landing page can show a
     * live "passwords generated" counter. No secret data is sent.
     *
     * @returns {void}
     */
    function reportGeneration() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) return;

        fetch('/generator/audit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: '{}',
        }).catch(() => {});
    }

    // Bind mode switcher
    if (modeSelect) {
        modeSelect.addEventListener('change', () => {
            const mode = modeSelect.value;
            if (randomOptions) randomOptions.classList.toggle('hidden', mode !== 'random');
            if (passphraseOptions) passphraseOptions.classList.toggle('hidden', mode !== 'passphrase');
            if (pronounceableOptions) pronounceableOptions.classList.toggle('hidden', mode !== 'pronounceable');
            doGenerate();
        });
    }

    if (lengthSlider && lengthVal) {
        lengthSlider.addEventListener('input', () => {
            lengthVal.textContent = lengthSlider.value;
            doGenerate();
        });
    }

    [optUpper, optLower, optNumbers, optSymbols, optAmbiguous].forEach((el) => {
        if (el) el.addEventListener('change', doGenerate);
    });

    ['gen-words', 'gen-separator', 'gen-passphrase-cap', 'gen-passphrase-num',
     'gen-syl', 'gen-pron-cap', 'gen-pron-num', 'gen-pron-sym'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', doGenerate);
            el.addEventListener('change', doGenerate);
        }
    });

    if (refreshBtn) refreshBtn.addEventListener('click', doGenerate);

    if (copyBtn && outputEl) {
        copyBtn.addEventListener('click', async () => {
            if (!outputEl.value) return;
            try {
                await navigator.clipboard.writeText(outputEl.value);
                const orig = copyBtn.innerHTML;
                copyBtn.innerHTML = `<span>${window.zkpmT('Copied!')}</span>`;
                setTimeout(() => { copyBtn.innerHTML = orig; }, 1500);
            } catch {
                window.zkpmToast(window.zkpmT('Copy failed.'), 'error');
            }
        });
    }

    // Initial generation
    doGenerate();
}
