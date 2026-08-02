<?php

use App\Models\Hospital;
use Illuminate\Support\Str;

if (! function_exists('firstName')) {
    /**
     * Retorna apenas o primeiro nome de uma pessoa, para saudações de mensagens.
     */
    function firstName(?string $fullName): string
    {
        $name = trim((string) $fullName);

        if ($name === '') {
            return '';
        }

        $titles = ['dr', 'dr.', 'dra', 'dra.', 'sr', 'sr.', 'sra', 'sra.', 'prof', 'prof.', 'ms', 'ms.'];

        $parts = preg_split('/\s+/', $name) ?: [];

        foreach ($parts as $part) {
            if (! in_array(Str::lower($part), $titles, true)) {
                return $part;
            }
        }

        return $parts[0] ?? '';
    }
}

if (! function_exists('greetingName')) {
    /**
     * Retorna a saudação com tratamento Dr./Dra. + primeiro nome, conforme o gênero.
     * Usa "Dr(a)." quando o gênero não foi informado.
     */
    function greetingName(?\App\Models\User $user): string
    {
        if ($user === null) {
            return '';
        }

        $first = firstName($user->name);

        return match ($user->gender) {
            'masculino' => 'Dr. '.$first,
            'feminino' => 'Dra. '.$first,
            default => $first,
        };
    }
}

if (! function_exists('inferGenderFromName')) {
    /**
     * Infere o gênero (masculino/feminino) pelo primeiro nome, usando uma lista de nomes comuns.
     * Retorna null quando não é possível inferir com segurança.
     */
    function inferGenderFromName(?string $fullName): ?string
    {
        $first = \Illuminate\Support\Str::lower(firstName($fullName));

        if ($first === '') {
            return null;
        }

        $femininos = [
            'maria', 'ana', 'julia', 'mariana', 'fernanda', 'patricia', 'patrícia', 'aline', 'juliana', 'camila',
            'larissa', 'bruna', 'brunella', 'eduarda', 'maressa', 'ursula', 'úrsula', 'sara', 'leticia', 'letícia',
            'carla', 'paula', 'renata', 'daniela', 'gabriela', 'amanda', 'beatriz', 'carolina', 'isabela', 'isabella',
            'luana', 'luciana', 'marilia', 'marília', 'vanessa', 'veronica', 'verônica', 'cristina', 'elisa', 'helena',
            'joana', 'laura', 'luisa', 'luísa', 'manuela', 'rafaela', 'sabrina', 'tatiana', 'vivian', 'yasmin',
            'adriana', 'alessandra', 'andressa', 'antonella', 'bianca', 'cecilia', 'cecília', 'clara', 'daniele',
            'elaine', 'fabiana', 'flavia', 'flávia', 'giselle', 'ingrid', 'jessica', 'jéssica', 'karen', 'larissa',
            'lilian', 'lorena', 'marcia', 'márcia', 'michele', 'monica', 'mônica', 'natalia', 'natália', 'pamela',
            'priscila', 'roberta', 'sandra', 'simone', 'sofia', 'suzana', 'talita', 'thais', 'thaís', 'valeria', 'valéria',
        ];

        $masculinos = [
            'joao', 'joão', 'jose', 'josé', 'pedro', 'carlos', 'paulo', 'lucas', 'marcos', 'rafael', 'gabriel',
            'alex', 'alessandro', 'alexandre', 'evandro', 'andre', 'andré', 'bruno', 'diego', 'eduardo', 'felipe',
            'fernando', 'francisco', 'guilherme', 'gustavo', 'henrique', 'igor', 'leonardo', 'luiz', 'marcelo',
            'mario', 'mário', 'mateus', 'matheus', 'miguel', 'paulo', 'ricardo', 'roberto', 'rodrigo', 'sergio',
            'sergio', 'thiago', 'tiago', 'victor', 'vinicius', 'vinícius', 'wagner', 'wellington', 'william',
            'daniel', 'danilo', 'david', 'edson', 'emerson', 'everton', 'fabio', 'fábio', 'fabio', 'gilberto', 'hugo',
            'ivan', 'jorge', 'julio', 'júlio', 'leandro', 'lucio', 'lúcio', 'maicon', 'mauricio', 'maurício',
            'nelson', 'paulo', 'raimundo', 'renato', 'ronaldo', 'samuel', 'sandro', 'sidney', 'valter', 'xande',
            'xander', 'zander', 'thallys', 'thalis', 'tallys', 'alexandre',
        ];

        if (in_array($first, $femininos, true)) {
            return 'feminino';
        }

        if (in_array($first, $masculinos, true)) {
            return 'masculino';
        }

        return null;
    }
}

if (! function_exists('currentHospital')) {
    /**
     * Hospital atualmente selecionado pelo gestor logado (persistido em session).
     * Retorna null se o usuário não gerencia nenhum hospital.
     */
    function currentHospital(): ?Hospital
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        $selectedId = session('current_hospital_id');

        if ($selectedId !== null) {
            /** @var Hospital|null $hospital */
            $hospital = $user->managedHospitals()->whereKey($selectedId)->first();

            if ($hospital !== null) {
                return $hospital;
            }
        }

        /** @var Hospital|null $first */
        $first = $user->managedHospitals()->orderBy('name')->first();

        if ($first !== null) {
            session(['current_hospital_id' => $first->id]);
        }

        return $first;
    }
}
