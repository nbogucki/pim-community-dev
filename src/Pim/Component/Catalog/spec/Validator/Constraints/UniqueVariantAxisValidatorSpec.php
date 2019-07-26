<?php

namespace spec\Pim\Component\Catalog\Validator\Constraints;

use PhpSpec\ObjectBehavior;
use Pim\Bundle\CatalogBundle\Doctrine\ORM\Repository\EntityWithFamilyVariantRepository;
use Pim\Component\Catalog\EntityWithFamilyVariant\Query\GetValuesOfSiblings;
use Pim\Component\Catalog\Exception\AlreadyExistingAxisValueCombinationException;
use Pim\Component\Catalog\FamilyVariant\EntityWithFamilyVariantAttributesProvider;
use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Component\Catalog\Model\EntityWithFamilyVariantInterface;
use Pim\Component\Catalog\Model\FamilyVariantInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Model\ProductModelInterface;
use Pim\Component\Catalog\Model\ValueCollectionInterface;
use Pim\Component\Catalog\Model\ValueInterface;
use Pim\Component\Catalog\Validator\Constraints\UniqueVariantAxis;
use Pim\Component\Catalog\Validator\UniqueAxesCombinationSet;
use Prophecy\Argument;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UniqueVariantAxisValidatorSpec extends ObjectBehavior
{
    function let(
        EntityWithFamilyVariantAttributesProvider $axesProvider,
        EntityWithFamilyVariantRepository $repository,
        UniqueAxesCombinationSet $uniqueAxesCombinationSet,
        GetValuesOfSiblings $getValuesOfSiblings,
        ExecutionContextInterface $context
    ) {
        $this->beConstructedWith($axesProvider, $repository, $uniqueAxesCombinationSet, $getValuesOfSiblings);
        $this->initialize($context);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Pim\Component\Catalog\Validator\Constraints\UniqueVariantAxisValidator');
    }

    function it_throws_an_exception_if_the_entity_is_not_supported(
        \DateTime $entity,
        UniqueVariantAxis $constraint
    ) {
        $this->shouldThrow(UnexpectedTypeException::class)->during('validate', [$entity, $constraint]);
    }

    function it_throws_an_exception_if_the_constraint_is_not_supported(
        EntityWithFamilyVariantInterface $entity,
        Constraint $constraint
    ) {
        $this->shouldThrow(UnexpectedTypeException::class)->during('validate', [$entity, $constraint]);
    }

    function it_raises_no_violation_if_the_entity_has_no_family_variant(
        ExecutionContextInterface $context,
        EntityWithFamilyVariantInterface $entity,
        UniqueVariantAxis $constraint
    ) {
        $entity->getFamilyVariant()->willReturn(null);

        $context->buildViolation(Argument::cetera())->shouldNotBeCalled();

        $this->validate($entity, $constraint);
    }

    function it_raises_no_violation_if_the_entity_has_no_parent(
        ExecutionContextInterface $context,
        FamilyVariantInterface $familyVariant,
        EntityWithFamilyVariantInterface $entity,
        UniqueVariantAxis $constraint
    ) {
        $entity->getFamilyVariant()->willReturn($familyVariant);
        $entity->getParent()->willReturn(null);

        $context->buildViolation(Argument::cetera())->shouldNotBeCalled();

        $this->validate($entity, $constraint);
    }

    function it_raises_no_violation_if_the_entity_has_no_axis(
        ExecutionContextInterface $context,
        EntityWithFamilyVariantAttributesProvider $axesProvider,
        FamilyVariantInterface $familyVariant,
        ProductModelInterface $parent,
        EntityWithFamilyVariantInterface $entity,
        UniqueVariantAxis $constraint
    ) {
        $entity->getParent()->willReturn($parent);
        $entity->getFamilyVariant()->willReturn($familyVariant);
        $axesProvider->getAxes($entity)->willReturn([]);

        $context->buildViolation(Argument::cetera())->shouldNotBeCalled();

        $this->validate($entity, $constraint);
    }

    function it_raises_no_violation_if_the_entity_has_no_sibling(
        ExecutionContextInterface $context,
        EntityWithFamilyVariantAttributesProvider $axesProvider,
        GetValuesOfSiblings $getValuesOfSiblings,
        FamilyVariantInterface $familyVariant,
        ProductModelInterface $parent,
        EntityWithFamilyVariantInterface $entity,
        UniqueVariantAxis $constraint
    ) {
        $entity->getParent()->willReturn($parent);
        $axesProvider->getAxes($entity)->willReturn([]);
        $entity->getFamilyVariant()->willReturn($familyVariant);
        $getValuesOfSiblings->for($entity)->willReturn([]);

        $context->buildViolation(Argument::cetera())->shouldNotBeCalled();

        $this->validate($entity, $constraint);
    }

    function it_raises_no_violation_if_axes_combination_is_empty(
        ExecutionContextInterface $context,
        EntityWithFamilyVariantAttributesProvider $axesProvider,
        UniqueAxesCombinationSet $uniqueAxesCombinationSet,
        FamilyVariantInterface $familyVariant,
        ProductModelInterface $parent,
        ProductModelInterface $entity,
        AttributeInterface $color,
        ValueCollectionInterface $values,
        ValueInterface $emptyValue,
        UniqueVariantAxis $constraint
    ) {
        $entity->getParent()->willReturn($parent);
        $entity->getFamilyVariant()->willReturn($familyVariant);
        $entity->getValuesForVariation()->willReturn($values);
        $values->getByCodes('color')->willReturn($emptyValue);
        $axesProvider->getAxes($entity)->willReturn([$color]);
        $color->getCode()->willReturn('color');
        $entity->getValue('color')->willReturn($emptyValue);
        $emptyValue->__toString()->willReturn('');
        $uniqueAxesCombinationSet->addCombination(Argument::any())->shouldNotBeCalled();

        $context->buildViolation(Argument::cetera())->shouldNotBeCalled();

        $this->validate($entity, $constraint);
    }

    function it_raises_no_violation_if_there_is_no_duplicate_in_any_sibling_product_model_from_database(
        ExecutionContextInterface $context,
        EntityWithFamilyVariantAttributesProvider $axesProvider,
        UniqueAxesCombinationSet $uniqueAxesCombinationSet,
        GetValuesOfSiblings $getValuesOfSiblings,
        FamilyVariantInterface $familyVariant,
        ProductModelInterface $parent,
        ProductModelInterface $entity,
        AttributeInterface $color,
        ValueCollectionInterface $values,
        ValueCollectionInterface $valuesOfFirstSibling,
        ValueCollectionInterface $valuesOfSecondSibling,
        ValueInterface $blue,
        ValueInterface $red,
        ValueInterface $yellow,
        UniqueVariantAxis $constraint
    ) {
        $entity->getParent()->willReturn($parent);
        $entity->getFamilyVariant()->willReturn($familyVariant);
        $axesProvider->getAxes($entity)->willReturn([$color]);
        $color->getCode()->willReturn('color');

        $values->getByCodes('color')->willReturn($blue);
        $entity->getValuesForVariation()->willReturn($values);

        $blue->__toString()->willReturn('[blue]');
        $red->__toString()->willReturn('[red]');
        $yellow->__toString()->willReturn('[yellow]');

        $getValuesOfSiblings->for($entity)->willReturn([
            'sibling1' => $valuesOfFirstSibling,
            'sibling2' => $valuesOfSecondSibling,
        ]);

        $valuesOfFirstSibling->getByCodes('color')->willReturn($red);
        $valuesOfSecondSibling->getByCodes('color')->willReturn($yellow);

        $uniqueAxesCombinationSet->addCombination($entity, '[blue]')->shouldBeCalled();

        $context->buildViolation(Argument::cetera())->shouldNotBeCalled();

        $this->validate($entity, $constraint);
    }

    function it_raises_no_violation_if_there_is_no_duplicate_in_any_sibling_variant_product_from_database(
        ExecutionContextInterface $context,
        EntityWithFamilyVariantAttributesProvider $axesProvider,
        UniqueAxesCombinationSet $uniqueAxesCombinationSet,
        GetValuesOfSiblings $getValuesOfSiblings,
        FamilyVariantInterface $familyVariant,
        ProductModelInterface $parent,
        ProductInterface $entity,
        AttributeInterface $color,
        ValueCollectionInterface $values,
        ValueCollectionInterface $valuesOfSibling,
        ValueInterface $blue,
        ValueInterface $red,
        UniqueVariantAxis $constraint
    ) {
        $entity->getParent()->willReturn($parent);
        $entity->getFamilyVariant()->willReturn($familyVariant);
        $entity->getValuesForVariation()->willReturn($values);
        $values->getByCodes('color')->willReturn($blue);

        $axesProvider->getAxes($entity)->willReturn([$color]);
        $color->getCode()->willReturn('color');

        $blue->__toString()->willReturn('[blue]');
        $red->__toString()->willReturn('[red]');

        $getValuesOfSiblings->for($entity)->willReturn(
            [
                'sibbling_identifier' => $valuesOfSibling,
            ]
        );

        $uniqueAxesCombinationSet->addCombination($entity, '[blue]')->shouldBeCalled();

        $context->buildViolation(Argument::cetera())->shouldNotBeCalled();

        $this->validate($entity, $constraint);
    }

    function it_raises_a_violation_if_there_is_a_duplicate_value_in_any_sibling_product_model(
        ExecutionContextInterface $context,
        EntityWithFamilyVariantAttributesProvider $axesProvider,
        UniqueAxesCombinationSet $uniqueAxesCombinationSet,
        GetValuesOfSiblings $getValuesOfSiblings,
        FamilyVariantInterface $familyVariant,
        ProductModelInterface $parent,
        ProductModelInterface $entity,
        AttributeInterface $color,
        ValueCollectionInterface $values,
        ValueCollectionInterface $valuesOfFirstSibling,
        ValueCollectionInterface $valuesOfSecondSibling,
        ValueInterface $blue,
        ValueInterface $yellow,
        UniqueVariantAxis $constraint,
        ConstraintViolationBuilderInterface $violation
    ) {
        $entity->getCode()->willReturn('entity_code');
        $entity->getParent()->willReturn($parent);
        $entity->getFamilyVariant()->willReturn($familyVariant);
        $entity->getValuesForVariation()->willReturn($values);
        $axesProvider->getAxes($entity)->willReturn([$color]);
        $color->getCode()->willReturn('color');

        $getValuesOfSiblings->for($entity)->willReturn(
            [
                'sibling1' => $valuesOfFirstSibling,
                'sibling2' => $valuesOfSecondSibling,
            ]
        );

        $values->getByCodes('color')->willReturn($blue);

        $valuesOfFirstSibling->getByCodes('color')->willReturn($yellow);
        $valuesOfSecondSibling->getByCodes('color')->willReturn($blue);

        $blue->__toString()->willReturn('[blue]');
        $yellow->__toString()->willReturn('[yellow]');

        $uniqueAxesCombinationSet->addCombination($entity, '[blue]')->shouldBeCalled();

        $context
            ->buildViolation(
                UniqueVariantAxis::DUPLICATE_VALUE_IN_PRODUCT_MODEL,
                [
                    '%values%' => '[blue]',
                    '%attributes%' => 'color',
                    '%validated_entity%' => 'entity_code',
                    '%sibling_with_same_value%' => 'sibling2',
                ]
            )
            ->willReturn($violation);
        $violation->atPath('attribute')->willReturn($violation);
        $violation->addViolation()->shouldBeCalled();

        $this->validate($entity, $constraint);
    }

    function it_raises_a_violation_if_there_is_a_duplicate_value_in_any_sibling_variant_product(
        ExecutionContextInterface $context,
        EntityWithFamilyVariantAttributesProvider $axesProvider,
        UniqueAxesCombinationSet $uniqueAxesCombinationSet,
        GetValuesOfSiblings $getValuesOfSiblings,
        FamilyVariantInterface $familyVariant,
        ProductModelInterface $parent,
        ProductInterface $entity,
        AttributeInterface $color,
        ValueCollectionInterface $values,
        ValueCollectionInterface $valuesOfFirstSibling,
        ValueCollectionInterface $valuesOfSecondSibling,
        ValueInterface $blue,
        ValueInterface $yellow,
        UniqueVariantAxis $constraint,
        ConstraintViolationBuilderInterface $violation
    ) {
        $entity->getIdentifier()->willReturn('my_identifier');
        $entity->getParent()->willReturn($parent);
        $entity->getFamilyVariant()->willReturn($familyVariant);
        $entity->getValuesForVariation()->willReturn($values);
        $axesProvider->getAxes($entity)->willReturn([$color]);
        $color->getCode()->willReturn('color');

        $getValuesOfSiblings->for($entity)->willReturn(
            [
                'sibling1' => $valuesOfFirstSibling,
                'sibling2' => $valuesOfSecondSibling,
            ]
        );

        $values->getByCodes('color')->willReturn($blue);

        $valuesOfFirstSibling->getByCodes('color')->willReturn($yellow);
        $valuesOfSecondSibling->getByCodes('color')->willReturn($blue);

        $blue->__toString()->willReturn('[blue]');
        $yellow->__toString()->willReturn('[yellow]');

        $uniqueAxesCombinationSet->addCombination($entity, '[blue]')->shouldBeCalled();

        $context
            ->buildViolation(
                UniqueVariantAxis::DUPLICATE_VALUE_IN_VARIANT_PRODUCT,
                [
                    '%values%' => '[blue]',
                    '%attributes%' => 'color',
                    '%validated_entity%' => 'my_identifier',
                    '%sibling_with_same_value%' => 'sibling2',
                ]
            )
            ->willReturn($violation);
        $violation->atPath('attribute')->willReturn($violation);
        $violation->addViolation()->shouldBeCalled();

        $this->validate($entity, $constraint);
    }

    function it_raises_a_violation_if_there_is_a_duplicate_value_in_any_sibling_product_model_with_multiple_attributes_in_axis(
        ExecutionContextInterface $context,
        EntityWithFamilyVariantAttributesProvider $axesProvider,
        UniqueAxesCombinationSet $uniqueAxesCombinationSet,
        GetValuesOfSiblings $getValuesOfSiblings,
        FamilyVariantInterface $familyVariant,
        ProductModelInterface $parent,
        ProductModelInterface $entity,
        AttributeInterface $color,
        AttributeInterface $size,
        ValueCollectionInterface $values,
        ValueCollectionInterface $valuesOfFirstSibling,
        ValueCollectionInterface $valuesOfSecondSibling,
        ValueInterface $blue,
        ValueInterface $yellow,
        UniqueVariantAxis $constraint,
        ConstraintViolationBuilderInterface $violation,
        ValueInterface $xl
    ) {
        $entity->getCode()->willReturn('entity_code');
        $entity->getParent()->willReturn($parent);
        $entity->getFamilyVariant()->willReturn($familyVariant);
        $entity->getValuesForVariation()->willReturn($values);

        $axesProvider->getAxes($entity)->willReturn([$color, $size]);
        $color->getCode()->willReturn('color');
        $size->getCode()->willReturn('size');

        $values->getByCodes('color')->willReturn($blue);
        $values->getByCodes('size')->willReturn($xl);

        $valuesOfFirstSibling->getByCodes('color')->willReturn($yellow);
        $valuesOfFirstSibling->getByCodes('size')->willReturn($xl);

        $valuesOfSecondSibling->getByCodes('color')->willReturn($blue);
        $valuesOfSecondSibling->getByCodes('size')->willReturn($xl);

        $getValuesOfSiblings->for($entity)->willReturn(
            [
                'sibling1' => $valuesOfFirstSibling,
                'sibling2' => $valuesOfSecondSibling,
            ]
        );

        $blue->__toString()->willReturn('[blue]');
        $yellow->__toString()->willReturn('[yellow]');
        $xl->__toString()->willReturn('[xl]');

        $uniqueAxesCombinationSet->addCombination($entity, '[blue],[xl]')->shouldBeCalled();

        $context
            ->buildViolation(
                UniqueVariantAxis::DUPLICATE_VALUE_IN_PRODUCT_MODEL,
                [
                    '%values%' => '[blue],[xl]',
                    '%attributes%' => 'color,size',
                    '%validated_entity%' => 'entity_code',
                    '%sibling_with_same_value%' => 'sibling2',
                ]
            )
            ->willReturn($violation);
        $violation->atPath('attribute')->willReturn($violation);
        $violation->addViolation()->shouldBeCalled();

        $this->validate($entity, $constraint);
    }

    function it_raises_a_violation_if_there_is_a_duplicate_value_in_any_sibling_variant_product_with_multiple_attributes_in_axis(
        ExecutionContextInterface $context,
        EntityWithFamilyVariantAttributesProvider $axesProvider,
        UniqueAxesCombinationSet $uniqueAxesCombinationSet,
        GetValuesOfSiblings $getValuesOfSiblings,
        FamilyVariantInterface $familyVariant,
        ProductModelInterface $parent,
        ProductInterface $entity,
        AttributeInterface $color,
        AttributeInterface $size,
        ValueCollectionInterface $values,
        ValueCollectionInterface $valuesOfFirstSibling,
        ValueCollectionInterface $valuesOfSecondSibling,
        ValueInterface $blue,
        ValueInterface $yellow,
        UniqueVariantAxis $constraint,
        ConstraintViolationBuilderInterface $violation,
        ValueInterface $xl
    ) {
        $entity->getIdentifier()->willReturn('entity_code');
        $entity->getParent()->willReturn($parent);
        $entity->getFamilyVariant()->willReturn($familyVariant);
        $entity->getValuesForVariation()->willReturn($values);

        $axesProvider->getAxes($entity)->willReturn([$color, $size]);
        $color->getCode()->willReturn('color');
        $size->getCode()->willReturn('size');

        $values->getByCodes('color')->willReturn($blue);
        $values->getByCodes('size')->willReturn($xl);

        $valuesOfFirstSibling->getByCodes('color')->willReturn($yellow);
        $valuesOfFirstSibling->getByCodes('size')->willReturn($xl);

        $valuesOfSecondSibling->getByCodes('color')->willReturn($blue);
        $valuesOfSecondSibling->getByCodes('size')->willReturn($xl);

        $getValuesOfSiblings->for($entity)->willReturn(
            [
                'sibling1' => $valuesOfFirstSibling,
                'sibling2' => $valuesOfSecondSibling,
            ]
        );

        $blue->__toString()->willReturn('[blue]');
        $yellow->__toString()->willReturn('[yellow]');
        $xl->__toString()->willReturn('[xl]');

        $uniqueAxesCombinationSet->addCombination($entity, '[blue],[xl]')->shouldBeCalled();

        $context
            ->buildViolation(
                UniqueVariantAxis::DUPLICATE_VALUE_IN_VARIANT_PRODUCT,
                [
                    '%values%' => '[blue],[xl]',
                    '%attributes%' => 'color,size',
                    '%validated_entity%' => 'entity_code',
                    '%sibling_with_same_value%' => 'sibling2',
                ]
            )
            ->willReturn($violation);
        $violation->atPath('attribute')->willReturn($violation);
        $violation->addViolation()->shouldBeCalled();

        $this->validate($entity, $constraint);
    }

    function it_raises_a_violation_if_there_is_a_duplicated_product_model_in_the_batch(
        ExecutionContextInterface $context,
        EntityWithFamilyVariantAttributesProvider $axesProvider,
        UniqueAxesCombinationSet $uniqueAxesCombinationSet,
        GetValuesOfSiblings $getValuesOfSiblings,
        FamilyVariantInterface $familyVariant,
        ProductModelInterface $entity1,
        ProductModelInterface $entity2,
        ProductModelInterface $parent,
        AttributeInterface $color,
        ValueCollectionInterface $values,
        ValueInterface $blue,
        AlreadyExistingAxisValueCombinationException $exception,
        UniqueVariantAxis $constraint,
        ConstraintViolationBuilderInterface $violation
    ) {
        $entity1->getParent()->willReturn($parent);
        $entity1->getFamilyVariant()->willReturn($familyVariant);
        $axesProvider->getAxes($entity1)->willReturn([$color]);
        $color->getCode()->willReturn('color');
        $getValuesOfSiblings->for($entity1)->willReturn([]);

        $entity2->getCode()->willReturn('entity_2');
        $entity2->getParent()->willReturn($parent);
        $entity2->getFamilyVariant()->willReturn($familyVariant);
        $getValuesOfSiblings->for($entity2)->willReturn([]);
        $axesProvider->getAxes($entity2)->willReturn([$color]);

        $values->getByCodes('color')->willReturn($blue);
        $entity1->getValuesForVariation()->willReturn($values);
        $entity2->getValuesForVariation()->willReturn($values);
        $blue->__toString()->willReturn('[blue]');

        $uniqueAxesCombinationSet->addCombination($entity2, '[blue]')->willThrow($exception->getWrappedObject());
        $exception->getEntityIdentifier()->willReturn('entity_1');

        $context
            ->buildViolation(
                UniqueVariantAxis::DUPLICATE_VALUE_IN_PRODUCT_MODEL,
                [
                    '%values%' => '[blue]',
                    '%attributes%' => 'color',
                    '%validated_entity%' => 'entity_2',
                    '%sibling_with_same_value%' => 'entity_1',
                ]
            )
            ->willReturn($violation);
        $violation->atPath('attribute')->willReturn($violation);
        $violation->addViolation()->shouldBeCalled();

        $this->validate($entity2, $constraint);
    }

    function it_raises_a_violation_if_there_is_a_duplicated_variant_product_in_the_batch(
        ExecutionContextInterface $context,
        EntityWithFamilyVariantAttributesProvider $axesProvider,
        UniqueAxesCombinationSet $uniqueAxesCombinationSet,
        GetValuesOfSiblings $getValuesOfSiblings,
        FamilyVariantInterface $familyVariant,
        ProductInterface $entity1,
        ProductInterface $entity2,
        ProductModelInterface $parent,
        AttributeInterface $color,
        ValueCollectionInterface $values,
        ValueInterface $blue,
        AlreadyExistingAxisValueCombinationException $exception,
        UniqueVariantAxis $constraint,
        ConstraintViolationBuilderInterface $violation
    ) {
        $entity1->getParent()->willReturn($parent);
        $entity1->getFamilyVariant()->willReturn($familyVariant);
        $axesProvider->getAxes($entity1)->willReturn([$color]);
        $color->getCode()->willReturn('color');
        $getValuesOfSiblings->for($entity1)->willReturn([]);

        $entity2->getIdentifier()->willReturn('entity_2');
        $entity2->getParent()->willReturn($parent);
        $entity2->getFamilyVariant()->willReturn($familyVariant);
        $getValuesOfSiblings->for($entity2)->willReturn([]);
        $axesProvider->getAxes($entity2)->willReturn([$color]);

        $values->getByCodes('color')->willReturn($blue);
        $entity1->getValuesForVariation()->willReturn($values);
        $entity2->getValuesForVariation()->willReturn($values);
        $blue->__toString()->willReturn('[blue]');

        $uniqueAxesCombinationSet->addCombination($entity2, '[blue]')->willThrow($exception->getWrappedObject());
        $exception->getEntityIdentifier()->willReturn('entity_1');

        $context
            ->buildViolation(
                UniqueVariantAxis::DUPLICATE_VALUE_IN_VARIANT_PRODUCT,
                [
                    '%values%' => '[blue]',
                    '%attributes%' => 'color',
                    '%validated_entity%' => 'entity_2',
                    '%sibling_with_same_value%' => 'entity_1',
                ]
            )
            ->willReturn($violation);
        $violation->atPath('attribute')->willReturn($violation);
        $violation->addViolation()->shouldBeCalled();

        $this->validate($entity2, $constraint);
    }
}
