<?php

declare(strict_types=1);

namespace Imhotep\Support;

class Pluralize
{
    protected static $consonants = "b|c|d|f|g|h|j|k|l|m|n|p|q|r|s|t|v|w|x|y|z";
    protected static $vowels = "a|e|i|o|u|y";

    protected static $immutable = [
        '*media', 'advice', 'aircraft', 'amoyese', 'art', 'audio', 'baggage', 'bison', 'borghese',
        'bream','buffalo', 'butter', 'carp', 'cattle', 'clothing', 'coal', 'cod', 'coitus',
        'compensation', 'congoese', 'cotton', 'data', 'deer', 'djinn', 'education', 'eland', 'elk',
        'emoji', 'equipment', 'evidence', 'faroese', 'feedback', 'fish', 'flounder', 'flour',
        'foochowese', 'food', 'furniture', 'genevese', 'genoese', 'gilbertese', 'gold', 'homework',
        'hottentotese', 'impatience', 'information', 'jedi', 'kin', 'kiplingese', 'knowledge',
        'kongoese', 'leather', 'love', 'lucchese', 'luggage', 'mackerel', 'maltese', 'management',
        'metadata', 'money', 'moose', 'music', 'nankingese', 'niasese', 'nutrition', 'offspring',
        'oil', 'patience', 'pekingese', 'piedmontese', 'pistoiese', 'plankton', 'pokemon', 'police',
        'polish', 'portuguese', 'rain', 'research', 'rice', 'salmon', 'sand', 'sarawakese', 'shavese',
        'sheep', 'silk', 'soap', 'spam', 'staff', 'sugar', 'swine', 'talent', 'toothpaste', 'traffic',
        'travel', 'trout', 'tuna', 'us', 'vermontese', 'vinegar', 'weather', 'wenchowese', 'wheat',
        'whiting', 'wildebeest', 'wood', 'wool', 'yengeese'];

    /*
    protected static $irregular = [
        yield new Substitution(new Word('atlas'), new Word('atlases'));
        yield new Substitution(new Word('beef'), new Word('beefs'));
        yield new Substitution(new Word('blouse'), new Word('blouses'));
        yield new Substitution(new Word('brother'), new Word('brothers'));
        yield new Substitution(new Word('cafe'), new Word('cafes'));
        yield new Substitution(new Word('chateau'), new Word('chateaux'));
        yield new Substitution(new Word('niveau'), new Word('niveaux'));
        yield new Substitution(new Word('child'), new Word('children'));
        yield new Substitution(new Word('canvas'), new Word('canvases'));
        yield new Substitution(new Word('cookie'), new Word('cookies'));
        yield new Substitution(new Word('corpus'), new Word('corpuses'));
        yield new Substitution(new Word('cow'), new Word('cows'));
        yield new Substitution(new Word('criterion'), new Word('criteria'));
        yield new Substitution(new Word('curriculum'), new Word('curricula'));
        yield new Substitution(new Word('demo'), new Word('demos'));
        yield new Substitution(new Word('domino'), new Word('dominoes'));
        yield new Substitution(new Word('echo'), new Word('echoes'));
        yield new Substitution(new Word('foot'), new Word('feet'));
        yield new Substitution(new Word('fungus'), new Word('fungi'));
        yield new Substitution(new Word('ganglion'), new Word('ganglions'));
        yield new Substitution(new Word('gas'), new Word('gases'));
        yield new Substitution(new Word('genie'), new Word('genies'));
        yield new Substitution(new Word('genus'), new Word('genera'));
        yield new Substitution(new Word('goose'), new Word('geese'));
        yield new Substitution(new Word('graffito'), new Word('graffiti'));
        yield new Substitution(new Word('hippopotamus'), new Word('hippopotami'));
        yield new Substitution(new Word('hoof'), new Word('hoofs'));
        yield new Substitution(new Word('human'), new Word('humans'));
        yield new Substitution(new Word('iris'), new Word('irises'));
        yield new Substitution(new Word('larva'), new Word('larvae'));
        yield new Substitution(new Word('leaf'), new Word('leaves'));
        yield new Substitution(new Word('lens'), new Word('lenses'));
        yield new Substitution(new Word('loaf'), new Word('loaves'));
        yield new Substitution(new Word('man'), new Word('men'));
        yield new Substitution(new Word('medium'), new Word('media'));
        yield new Substitution(new Word('memorandum'), new Word('memoranda'));
        yield new Substitution(new Word('money'), new Word('monies'));
        yield new Substitution(new Word('mongoose'), new Word('mongooses'));
        yield new Substitution(new Word('motto'), new Word('mottoes'));
        yield new Substitution(new Word('move'), new Word('moves'));
        yield new Substitution(new Word('mythos'), new Word('mythoi'));
        yield new Substitution(new Word('niche'), new Word('niches'));
        yield new Substitution(new Word('nucleus'), new Word('nuclei'));
        yield new Substitution(new Word('numen'), new Word('numina'));
        yield new Substitution(new Word('occiput'), new Word('occiputs'));
        yield new Substitution(new Word('octopus'), new Word('octopuses'));
        yield new Substitution(new Word('opus'), new Word('opuses'));
        yield new Substitution(new Word('ox'), new Word('oxen'));
        yield new Substitution(new Word('passerby'), new Word('passersby'));
        yield new Substitution(new Word('penis'), new Word('penises'));
        yield new Substitution(new Word('person'), new Word('people'));
        yield new Substitution(new Word('plateau'), new Word('plateaux'));
        yield new Substitution(new Word('runner-up'), new Word('runners-up'));
        yield new Substitution(new Word('safe'), new Word('safes'));
        yield new Substitution(new Word('sex'), new Word('sexes'));
        yield new Substitution(new Word('soliloquy'), new Word('soliloquies'));
        yield new Substitution(new Word('son-in-law'), new Word('sons-in-law'));
        yield new Substitution(new Word('syllabus'), new Word('syllabi'));
        yield new Substitution(new Word('testis'), new Word('testes'));
        yield new Substitution(new Word('thief'), new Word('thieves'));
        yield new Substitution(new Word('tooth'), new Word('teeth'));
        yield new Substitution(new Word('tornado'), new Word('tornadoes'));
        yield new Substitution(new Word('trilby'), new Word('trilbys'));
        yield new Substitution(new Word('turf'), new Word('turfs'));
        yield new Substitution(new Word('valve'), new Word('valves'));
        yield new Substitution(new Word('volcano'), new Word('volcanoes'));
        yield new Substitution(new Word('abuse'), new Word('abuses'));
        yield new Substitution(new Word('avalanche'), new Word('avalanches'));
        yield new Substitution(new Word('cache'), new Word('caches'));
        yield new Substitution(new Word('criterion'), new Word('criteria'));
        yield new Substitution(new Word('curve'), new Word('curves'));
        yield new Substitution(new Word('emphasis'), new Word('emphases'));
        yield new Substitution(new Word('foe'), new Word('foes'));
        yield new Substitution(new Word('grave'), new Word('graves'));
        yield new Substitution(new Word('hoax'), new Word('hoaxes'));
        yield new Substitution(new Word('medium'), new Word('media'));
        yield new Substitution(new Word('neurosis'), new Word('neuroses'));
        yield new Substitution(new Word('oasis'), new Word('oases'));
    ];
    */

    public static function plural($word)
    {
        if (in_array(strtolower($word), self::$immutable)) {
            return $word;
        }

        $rules = [
            ['(s)tatus$', '\1\2tatuses'],
            ['(quiz)$', '\1zes'],
            ['^(ox)$', '\1\2en'],
            ['([m|l])ouse$', '\1ice'],
            ['(matr|vert|ind)(ix|ex)$', '\1ices'],
            ['(s|x|ch|ss|sh)$', '\1es'],
            ['([^aeiouy]|qu)y$', '\1ies'],
            ['(hive|gulf)$', '\1s'],
            ['(?:([^f])fe|([lr])f)$', '\1\2ves'],
            ['sis$', 'ses'],
            ['([ti])um$', '\1a'],
            ['(tax)on$', '\1a'],
            ['(c)riterion$', '\1riteria'],
            ['(p)erson$', '\1eople'],
            ['(m)an$', '\1en'],
            ['(c)hild$', '\1hildren'],
            ['(f)oot$', '\1eet'],
            ['(buffal|her|potat|tomat|volcan)o$', '\1\2oes'],
            ['(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|vir)us$', '\1i'],
            ['us$', 'uses'],
            ['(alias)$', '\1es'],
            ['(analys|ax|cris|test|thes)is$', '\1es'],
            ['$', 's']
        ];

        foreach ($rules as $rule) {
            if (self::matches($word, $rule[0])) {
                return self::transform($word, $rule[0], $rule[1]);
            }
        }

        return $word;

        /*

        окончание -s, -ss, -sh, -ch или -x   = +es

        согласная + y = es
        гласная + y = s
            */
    }

    public static function singular($word)
    {

    }

    protected static function matches($word, $pattern){
       return preg_match("/{$pattern}/i", $word) === 1;
    }

    protected static function transform($word, $pattern, $replacement){
        return (string)preg_replace("/{$pattern}/i", $replacement, $word);
    }
}